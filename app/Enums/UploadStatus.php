<?php

namespace App\Enums;

enum UploadStatus: string
{
    case Pending = 'pending';     // multipart initiated, parts uploading
    case Uploaded = 'uploaded';   // all parts complete, R2 object assembled
    case Scanning = 'scanning';   // checksum + malware scan running
    case Published = 'published'; // clean, attached to a download link
    case Failed = 'failed';

    public function label(): string
    {
        return __('monitor.status_'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Uploaded => 'info',
            self::Scanning => 'warning',
            self::Published => 'success',
            self::Failed => 'danger',
        };
    }
}
