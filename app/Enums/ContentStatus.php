<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('content.status.draft'),
            self::Pending => __('content.status.pending'),
            self::Published => __('content.status.published'),
            self::Rejected => __('content.status.rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
