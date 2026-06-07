<?php

namespace App\Services\Upload;

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Talks to Cloudflare R2 over the S3 multipart API.
 *
 * The browser uploads each part straight to R2 using the presigned URLs this
 * service mints — bytes never travel through PHP, so a single object can be up
 * to the S3 limit (10,000 parts) far beyond any PHP/Nginx body limit.
 */
class R2UploadService
{
    private string $disk = 'r2';

    /** True once S3-compatible credentials are present (R2 / AWS S3 / iDrive e2). */
    public function isConfigured(): bool
    {
        $c = config("filesystems.disks.{$this->disk}");

        return ! empty($c['key'])
            && ! empty($c['secret'])
            && ! empty($c['bucket']);
    }

    public function client(): S3Client
    {
        /** @var AwsS3V3Adapter $adapter */
        $adapter = Storage::disk($this->disk);

        if (! method_exists($adapter, 'getClient')) {
            throw new RuntimeException('The r2 disk is not an S3 adapter. Check config/filesystems.php.');
        }

        return $adapter->getClient();
    }

    public function bucket(): string
    {
        $bucket = config("filesystems.disks.{$this->disk}.bucket");

        if (empty($bucket)) {
            throw new RuntimeException('R2_BUCKET is not configured.');
        }

        return $bucket;
    }

    public function ttlMinutes(): int
    {
        return (int) env('UPLOAD_URL_TTL', 60);
    }

    /**
     * Start a multipart upload and return the R2 UploadId.
     */
    public function createMultipartUpload(string $key, ?string $contentType = null): string
    {
        $result = $this->client()->createMultipartUpload(array_filter([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'ContentType' => $contentType,
        ]));

        return $result['UploadId'];
    }

    /**
     * Presign a single part. The browser issues a PUT to this URL with the
     * raw bytes of that chunk and reads the ETag from the response headers.
     */
    public function presignPart(string $key, string $uploadId, int $partNumber): string
    {
        $command = $this->client()->getCommand('UploadPart', [
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $request = $this->client()->createPresignedRequest($command, "+{$this->ttlMinutes()} minutes");

        return (string) $request->getUri();
    }

    /**
     * Stream a finished local file to S3 in a single PUT (proxy mode). Avoids the
     * multipart API for providers that don't support it (e.g. iDrive e2). The S3
     * single-object PUT limit is 5GB.
     */
    public function putObjectFromFile(string $key, string $absolutePath, ?string $contentType = null): void
    {
        $args = array_filter([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'SourceFile' => $absolutePath,
            'ContentType' => $contentType,
        ], fn ($v) => $v !== null);

        // Some S3-compatible providers (e.g. iDrive e2) return transient 503/500s;
        // retry a few times with backoff before giving up.
        $attempts = 0;
        beginning:
        try {
            $this->client()->putObject($args);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $status = $e->getStatusCode();
            if (++$attempts < 4 && ($status === 503 || $status === 500 || $status === null)) {
                usleep(($attempts * 700) * 1000); // 0.7s, 1.4s, 2.1s
                goto beginning;
            }
            throw $e;
        }
    }

    /**
     * Presign a batch of parts at once: [partNumber => url].
     *
     * @param  array<int>  $partNumbers
     * @return array<int,string>
     */
    public function presignParts(string $key, string $uploadId, array $partNumbers): array
    {
        $urls = [];
        foreach ($partNumbers as $n) {
            $urls[$n] = $this->presignPart($key, $uploadId, $n);
        }

        return $urls;
    }

    /**
     * Finalise the object once every part has been uploaded.
     *
     * @param  array<int,array{PartNumber:int,ETag:string}>  $parts
     */
    public function completeMultipartUpload(string $key, string $uploadId, array $parts): void
    {
        // S3 requires parts ordered by PartNumber.
        usort($parts, fn ($a, $b) => $a['PartNumber'] <=> $b['PartNumber']);

        $this->client()->completeMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $parts],
        ]);
    }

    /**
     * Cancel an in-flight multipart upload (frees the already-uploaded parts).
     */
    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        $this->client()->abortMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);
    }

    /**
     * Short-lived presigned GET URL used to actually serve a download.
     */
    public function temporaryDownloadUrl(string $key, ?string $downloadName = null): string
    {
        $options = [];
        if ($downloadName) {
            $options['ResponseContentDisposition'] =
                'attachment; filename="'.addslashes($downloadName).'"';
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $key,
            now()->addMinutes($this->ttlMinutes()),
            $options,
        );
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

    /**
     * Build a collision-free object key for a freshly uploaded file.
     */
    public function buildKey(string $originalName): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $slug = \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';
        $rand = bin2hex(random_bytes(8));

        return 'files/'.date('Y/m').'/'.$slug.'-'.$rand.($ext ? '.'.$ext : '');
    }
}
