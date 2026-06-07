<?php

namespace App\Services\Upload;

/**
 * Streams an R2 object through a hash context in small chunks so even a 30GB
 * file is hashed with O(1) memory. Never loads the whole object at once.
 */
class ChecksumService
{
    public function __construct(private readonly R2UploadService $r2)
    {
    }

    /**
     * @return array{sha256:string,md5:string}
     */
    public function forKey(string $key): array
    {
        $client = $this->r2->client();
        $client->registerStreamWrapper();

        $uri = 's3://'.$this->r2->bucket().'/'.$key;
        $stream = @fopen($uri, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Unable to open R2 stream for {$key}");
        }

        return $this->hashStream($stream);
    }

    /**
     * Hash a file on the local disk (used by the local-storage fallback).
     *
     * @return array{sha256:string,md5:string}
     */
    public function forPath(string $absolutePath): array
    {
        $stream = @fopen($absolutePath, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Unable to open local file {$absolutePath}");
        }

        return $this->hashStream($stream);
    }

    /**
     * @param  resource  $stream
     * @return array{sha256:string,md5:string}
     */
    private function hashStream($stream): array
    {
        $sha = hash_init('sha256');
        $md5 = hash_init('md5');

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 8 * 1024 * 1024); // 8MB
                if ($chunk === false) {
                    break;
                }
                hash_update($sha, $chunk);
                hash_update($md5, $chunk);
            }
        } finally {
            fclose($stream);
        }

        return [
            'sha256' => hash_final($sha),
            'md5' => hash_final($md5),
        ];
    }
}
