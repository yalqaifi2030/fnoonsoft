<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;
use Livewire\Attributes\On;

/**
 * Lets a Software create/edit page receive files from the in-form big-file
 * uploader (Uppy, same engine as the upload panel). When a chunked upload
 * finishes, the browser dispatches `bigFileUploaded`; this appends a matching
 * `r2` download link to the form's repeater so it saves with the record.
 */
trait HandlesBigFileUpload
{
    #[On('bigFileUploaded')]
    public function addUploadedLink(string $key, ?int $size = null, ?string $name = null): void
    {
        $links = data_get($this->data, 'downloadLinks', []) ?? [];

        $links[(string) Str::uuid()] = [
            'label' => $name ? pathinfo($name, PATHINFO_FILENAME) : null,
            'type' => 'r2',
            'r2_key' => $key,
            'external_url' => null,
            'original_filename' => $name,
            'size_bytes' => $size,
            'is_portable' => false,
        ];

        data_set($this->data, 'downloadLinks', $links);
    }
}
