<?php

namespace App\Filament\Pages\Auth;

use App\Rules\MeaningfulName;
use App\Rules\ReservedName;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Register as BaseRegister;

/**
 * Member registration with two guards on the display name:
 *  • ReservedName — blocks impersonation ("Admin", "Support", "مدير"…),
 *  • MeaningfulName — rejects gibberish / bot names ("UqOTgcNRMAgovYkoMBukn").
 */
class Register extends BaseRegister
{
    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()
            ->rule(new ReservedName)
            ->rule(new MeaningfulName);
    }
}
