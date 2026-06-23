<?php

namespace App\Console\Commands;

use App\Models\LearningVideo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Optimise uploaded learning videos with ffmpeg (transcode to faststart H.264
 * ≤1080p + generate a poster thumbnail). Runs from a ROOT cron — the panel's
 * process-protection blocks the web (www) user from executing binaries, so this
 * deliberately runs outside the www queue worker.
 */
class ProcessLearningVideos extends Command
{
    protected $signature = 'learn:process-videos {--id= : Process a single video id}';

    protected $description = 'Transcode + thumbnail pending uploaded learning videos via ffmpeg';

    public function handle(): int
    {
        // Single-flight: skip if a previous run is still working.
        $lockPath = storage_path('app/.learn-videos.lock');
        $lock = fopen($lockPath, 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return self::SUCCESS;
        }

        $query = LearningVideo::query()->where('source', 'upload');
        if ($id = $this->option('id')) {
            $query->whereKey($id);
        } else {
            $query->where('is_processing', true);
        }

        foreach ($query->get() as $video) {
            try {
                $this->process($video);
            } catch (\Throwable $e) {
                Log::error('[fnoon] learning video processing crashed', ['id' => $video->id, 'msg' => $e->getMessage()]);
                $video->forceFill(['is_processing' => false])->saveQuietly();
            }
        }

        flock($lock, LOCK_UN);
        fclose($lock);

        return self::SUCCESS;
    }

    private function process(LearningVideo $video): void
    {
        $disk = Storage::disk('public');
        if (! $video->file_path || ! $disk->exists($video->file_path)) {
            $video->forceFill(['is_processing' => false])->saveQuietly();

            return;
        }

        $ffmpeg = config('media.ffmpeg');
        $ffprobe = config('media.ffprobe');
        $input = $disk->path($video->file_path);

        // representative frame timestamp
        $dur = 0.0;
        $do = [];
        @exec(escapeshellarg($ffprobe).' -v error -show_entries format=duration -of csv=p=0 '.escapeshellarg($input), $do);
        if (! empty($do[0]) && is_numeric($do[0])) {
            $dur = (float) $do[0];
        }
        $thumbAt = $dur > 1 ? min($dur * 0.25, max($dur - 0.5, 1)) : 1;

        // poster thumbnail
        $thumbRel = 'learning-videos/thumbs/'.$video->id.'-'.Str::random(6).'.jpg';
        $thumbAbs = $disk->path($thumbRel);
        @mkdir(dirname($thumbAbs), 0775, true);
        @exec(sprintf(
            '%s -y -ss %s -i %s -frames:v 1 -vf %s -q:v 3 %s 2>&1',
            escapeshellarg($ffmpeg), escapeshellarg((string) $thumbAt), escapeshellarg($input),
            escapeshellarg("scale='min(1280,iw)':-2"), escapeshellarg($thumbAbs)
        ));
        $haveThumb = is_file($thumbAbs) && filesize($thumbAbs) > 0;

        // transcode: clean H.264, <=1080p, faststart
        $outRel = 'learning-videos/'.$video->id.'-hd-'.Str::random(6).'.mp4';
        $outAbs = $disk->path($outRel);
        $scale = "scale='min(1920,iw)':'min(1080,ih)':force_original_aspect_ratio=decrease:force_divisible_by=2";
        $vo = [];
        @exec(sprintf(
            '%s -y -i %s -vf %s -c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p -c:a aac -b:a 128k -movflags +faststart %s 2>&1',
            escapeshellarg($ffmpeg), escapeshellarg($input), escapeshellarg($scale), escapeshellarg($outAbs)
        ), $vo, $vrc);
        $haveOut = $vrc === 0 && is_file($outAbs) && filesize($outAbs) > 100_000;

        // keep new files owned by the web user (this runs as root)
        foreach ([$haveThumb ? $thumbAbs : null, $haveOut ? $outAbs : null] as $p) {
            if ($p) {
                @chown($p, 'www');
                @chgrp($p, 'www');
                @chmod($p, 0644);
            }
        }

        $oldPath = $video->file_path;
        $updates = ['is_processing' => false];
        if ($haveThumb) {
            $updates['thumbnail_path'] = $thumbRel;
        }
        if ($haveOut) {
            $updates['file_path'] = $outRel;
        }
        $video->forceFill($updates)->saveQuietly();

        if ($haveOut && $oldPath && $oldPath !== $outRel) {
            try {
                $disk->delete($oldPath);
            } catch (\Throwable $e) {
            }
        } elseif (! $haveOut) {
            Log::warning('[fnoon] transcode failed', ['id' => $video->id, 'rc' => $vrc, 'tail' => implode("\n", array_slice($vo, -4))]);
            if (is_file($outAbs)) {
                @unlink($outAbs);
            }
        }

        $this->info("processed #{$video->id} thumb=".($haveThumb ? 'y' : 'n').' hd='.($haveOut ? 'y' : 'n'));
    }
}
