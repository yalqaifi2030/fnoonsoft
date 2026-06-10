<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Validation\Rule;

/**
 * Profile page (in the topbar user menu): a public "creator" identity (avatar,
 * name, @username, bio, links) plus the account section (email, password). The
 * username powers the public profile page at /u/{username}.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('profile.section_public'))
                ->description(__('profile.section_public_hint'))
                ->schema([
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

                    TextInput::make('username')
                        ->label(__('profile.username'))
                        ->helperText(__('profile.username_hint'))
                        ->prefix('@')
                        ->alphaDash()
                        ->minLength(3)
                        ->maxLength(30)
                        ->unique(ignoreRecord: true)
                        ->rule(Rule::notIn(['admin', 'upload', 'dashboard', 'api', 'login', 'register', 'u', 'd']))
                        ->dehydrateStateUsing(fn (?string $state) => $state ? strtolower($state) : null),

                    Textarea::make('bio')
                        ->label(__('profile.bio'))
                        ->maxLength(500)
                        ->rows(3),

                    TextInput::make('website')
                        ->label(__('profile.website'))
                        ->url()
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->maxLength(255),

                    TextInput::make('twitter')
                        ->label(__('profile.twitter'))
                        ->prefix('@')
                        ->maxLength(50),

                    TextInput::make('github')
                        ->label(__('profile.github'))
                        ->prefix('@')
                        ->maxLength(50),
                ]),

            Section::make(__('profile.section_account'))
                ->schema([
                    $this->getEmailFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                ]),
        ]);
    }
}
