<?php

namespace App\Services\Upload;

use App\Models\UploadSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Local-disk fallback for the multipart upload engine.
 *
 * Mirrors the R2 multipart flow so the front-end (Uppy AwsS3Multipart) is
 * identical: the browser PUTs each chunk to a signed same-origin endpoint, the
 * parts are stored under storage/app/uploads/tmp/{uuid}, then assembled into
 * the final object on the `local` disk. Used automatically whenever real R2
 * credentials are absent — the engine switches to R2 the moment they exist.
 */
class LocalUploadService
{
    private string $disk = 'local';

    public function disk(): string
    {
        return $this->disk;
    }

    private function tmpDir(UploadSession $session): string
    {
        return 'uploads/tmp/'.$session->uuid;
    }

    /** No real backend handshake — just a synthetic upload id for parity. */
    public function createMultipart(): string
    {
        return 'local-'.Str::uuid()->toString();
    }

    /** Store one chunk and return an S3-style quoted ETag (md5 of the bytes). */
    public function storePart(UploadSession $session, int $partNumber, string $contents): string
    {
        Storage::disk($this->disk)->put($this->tmpDir($session).'/'.$partNumber.'.part', $contents);

        return '"'.md5($contents).'"';
    }

    /**
     * Assemble all uploaded parts (ordered by PartNumber) into the final object.
     *
     * @param  array<int,array{PartNumber:int,ETag:string}>  $parts
     */
    public function completeMultipart(UploadSession $session, array $parts): void
    {
        usort($parts, fn ($a, $b) => $a['PartNumber'] <=> $b['PartNumber']);

        $disk = Storage::disk($this->disk);
        $finalPath = $disk->path($session->r2_key);

        if (! is_dir(dirname($finalPath))) {
            mkdir(dirname($finalPath), 0775, true);
        }

        $out = fopen($finalPath, 'wb');
        if ($out === false) {
            throw new RuntimeException('Unable to open final file for writing: '.$session->r2_key);
        }

        try {
            foreach ($parts as $part) {
                $n = (int) $part['PartNumber'];
                $partPath = $disk->path($this->tmpDir($session).'/'.$n.'.part');

                if (! is_file($partPath)) {
                    throw new RuntimeException("Missing uploaded part #{$n}.");
                }

                $in = fopen($partPath, 'rb');
                if ($in === false) {
                    throw new RuntimeException("Unable to read part #{$n}.");
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } finally {
            fclose($out);
        }

        $disk->deleteDirectory($this->tmpDir($session));
    }

    public function abort(UploadSession $session): void
    {
        Storage::disk($this->disk)->deleteDirectory($this->tmpDir($session));
    }

    public function exists(string $key): bool
    {
        return Storage::disk($this->disk)->exists($key);
    }

    public function size(string $key): int
    {
        return (int) Storage::disk($this->disk)->size($key);
    }

    public function delete(string $key): void
    {
        Storage::disk($this->disk)->delete($key);
    }

    public function path(string $key): string
    {
        return Storage::disk($this->disk)->path($key);
    }
}
