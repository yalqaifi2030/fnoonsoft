<?php

namespace App\Filament\Forms\Components;

use App\Support\Icons;
use Filament\Forms\Components\Field;

/**
 * A visual icon picker: a searchable grid of Font Awesome icons that stores the
 * selected class (e.g. "fa-solid fa-globe") as the field's state.
 */
class IconPicker extends Field
{
    protected string $view = 'filament.forms.components.icon-picker';

    /** @return list<string> */
    public function getIcons(): array
    {
        return Icons::list();
    }
}
