<?php

namespace App\Jobs;

use App\Models\LearningVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Optimise an uploaded learning video with ffmpeg:
 *  - transcode to clean H.264 (≤1080p), web-fast (+faststart) so it streams &
 *    seeks instantly;
 *  - generate a real poster thumbnail from a representative frame.
 * Runs on the queue (transcoding is slow). Never deletes the original until a
 * valid output exists.
 */
class ProcessLearningVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3000;

    public int $tries = 1;

    public function __construct(public int $videoId) {}

    public function handle(): void
    {
        $video = LearningVideo::find($this->videoId);
        if (! $video || $video->source !== 'upload' || ! $video->file_path) {
            return;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($video->file_path)) {
            $video->forceFill(['is_processing' => false])->saveQuietly();

            return;
        }

        $ffmpeg = config('media.ffmpeg');
        $ffprobe = config('media.ffprobe');
        $input = $disk->path($video->file_path);

        // --- pick a representative frame timestamp ---
        $dur = 0.0;
        $do = [];
        @exec(escapeshellarg($ffprobe).' -v error -show_entries format=duration -of csv=p=0 '.escapeshellarg($input), $do);
        if (! empty($do[0]) && is_numeric($do[0])) {
            $dur = (float) $do[0];
        }
        $thumbAt = $dur > 1 ? min($dur * 0.25, max($dur - 0.5, 1)) : 1;

        // --- poster thumbnail ---
        $thumbRel = 'learning-videos/thumbs/'.$video->id.'-'.Str::random(6).'.jpg';
        $thumbAbs = $disk->path($thumbRel);
        @mkdir(dirname($thumbAbs), 0775, true);
        $tcmd = sprintf(
            '%s -y -ss %s -i %s -frames:v 1 -vf %s -q:v 3 %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg((string) $thumbAt),
            escapeshellarg($input),
            escapeshellarg("scale='min(1280,iw)':-2"),
            escapeshellarg($thumbAbs)
        );
        exec($tcmd, $to, $trc);
        $haveThumb = is_file($thumbAbs) && filesize($thumbAbs) > 0;

        // --- transcode: clean H.264, ≤1080p, faststart ---
        $outRel = 'learning-videos/'.$video->id.'-hd-'.Str::random(6).'.mp4';
        $outAbs = $disk->path($outRel);
        $scale = "scale='min(1920,iw)':'min(1080,ih)':force_original_aspect_ratio=decrease:force_divisible_by=2";
        $vcmd = sprintf(
            '%s -y -i %s -vf %s -c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p -c:a aac -b:a 128k -movflags +faststart %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($input),
            escapeshellarg($scale),
            escapeshellarg($outAbs)
        );
        exec($vcmd, $vo, $vrc);
        $haveOut = $vrc === 0 && is_file($outAbs) && filesize($outAbs) > 100_000;

        // --- commit results ---
        $oldPath = $video->file_path;
        $updates = ['is_processing' => false];
        if ($haveThumb) {
            $updates['thumbnail_path'] = $thumbRel;
        }
        if ($haveOut) {
            $updates['file_path'] = $outRel;
        }
        $video->forceFill($updates)->saveQuietly();

        if ($haveOut) {
            if ($oldPath && $oldPath !== $outRel) {
                try {
                    $disk->delete($oldPath);
                } catch (\Throwable $e) {
                }
            }
        } else {
            Log::warning('[fnoon] learning video transcode failed', ['id' => $video->id, 'rc' => $vrc, 'tail' => implode("\n", array_slice($vo, -5))]);
            if (is_file($outAbs)) {
                @unlink($outAbs);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $v = LearningVideo::find($this->videoId);
        if ($v) {
            $v->forceFill(['is_processing' => false])->saveQuietly();
        }
    }
}
