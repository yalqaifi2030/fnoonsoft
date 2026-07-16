<?php

namespace App\Filament\Upload\Widgets;

use App\Filament\Upload\Pages\UploadCenter;
use Filament\Widgets\Widget;

class UploaderWelcome extends Widget
{
    protected static string $view = 'filament.upload.widgets.uploader-welcome';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -3;

    // Static hero — render immediately instead of lazy-loading.
    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'name' => auth()->user()?->name,
            'uploadUrl' => UploadCenter::getUrl(),
            'maxGb' => (int) (env('UPLOAD_MAX_BYTES', 42949672960) / 1073741824),
        ];
    }
}
