<?php

namespace App\Filament\Resources\InteractiveLabResource\Pages;

use App\Filament\Resources\InteractiveLabResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveLab extends ViewRecord
{
    use \App\Filament\Concerns\TranslatableFormState;

    protected static string $resource = InteractiveLabResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('view_site')
                ->label(__('learn_admin.action.view_site'))
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => route('learn.lab', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
