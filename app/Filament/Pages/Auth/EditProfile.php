<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

/**
 * Profile page (in the topbar user menu) with an avatar uploader on top of the
 * default name / email / password fields. The image also shows in the navbar.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                FileUpload::make('avatar')
                    ->label(__('profile.avatar'))
                    ->helperText(__('profile.avatar_hint'))
                    ->avatar()
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->disk('public')
                    ->directory('avatars')
                    ->maxSize(2048),

                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]),
        ]);
    }
}
