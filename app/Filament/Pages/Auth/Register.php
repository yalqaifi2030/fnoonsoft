<?php

namespace App\Filament\Pages\Auth;

use App\Rules\ReservedName;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Register as BaseRegister;

/**
 * Member registration with reserved/impersonation-name protection on the display
 * name (so nobody signs up as "Admin", "Super Admin", "Support", "مدير"…).
 */
class Register extends BaseRegister
{
    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()->rule(new ReservedName);
    }
}
