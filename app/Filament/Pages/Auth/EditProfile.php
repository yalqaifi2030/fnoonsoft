<?php

namespace App\Filament\Pages\Auth;

use App\Rules\MeaningfulName;
use App\Rules\ReservedName;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

/**
 * Profile page (topbar user menu): a public "creator" identity (avatar, cover,
 * name, @username, bio, links) + the account section (email, password). The
 * username powers the public profile page at /u/{username}.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            // --- Public identity ---------------------------------------
            Section::make(__('profile.section_public'))
                ->description(__('profile.section_public_hint'))
                ->icon('heroicon-o-user-circle')
                ->columns(2)
                ->schema([
                    FileUpload::make('cover')
                        ->label(__('profile.cover'))
                        ->helperText(__('profile.cover_hint'))
                        ->image()->imageEditor()
                        ->imageEditorAspectRatios(['16:9', '21:9'])
                        ->disk('public')->directory('covers')->maxSize(4096)
                        ->columnSpanFull(),

                    FileUpload::make('avatar')
                        ->label(__('profile.avatar'))
                        ->helperText(__('profile.avatar_hint'))
                        ->avatar()->image()->imageEditor()->circleCropper()
                        ->disk('public')->directory('avatars')->maxSize(2048)
                        ->columnSpan(1),

                    Placeholder::make('public_link')
                        ->label(__('profile.public_profile'))
                        ->content(function (): HtmlString|string {
                            $user = auth()->user();
                            if ($user && method_exists($user, 'hasPublicProfile') && $user->hasPublicProfile()) {
                                $url = $user->publicProfileUrl();

                                return new HtmlString(
                                    '<a href="'.e($url).'" target="_blank" rel="noopener" style="color:#006C35;font-weight:600;">'
                                    .e(preg_replace('#^https?://#', '', (string) $url)).' <span style="font-size:.8em">↗</span></a>'
                                );
                            }

                            return __('profile.public_profile_hint');
                        })
                        ->columnSpan(1),

                    $this->getNameFormComponent()
                        ->prefixIcon('heroicon-m-user')
                        ->rules($this->nameRules())
                        ->columnSpan(1),

                    TextInput::make('username')
                        ->label(__('profile.username'))
                        ->helperText(__('profile.username_hint'))
                        ->prefix('@')
                        ->alphaDash()->minLength(3)->maxLength(30)
                        ->unique(ignoreRecord: true)
                        ->rule(Rule::notIn(['admin', 'upload', 'dashboard', 'api', 'login', 'register', 'u', 'd']))
                        ->rules($this->reservedRule())
                        ->dehydrateStateUsing(fn (?string $state) => $state ? strtolower($state) : null)
                        ->columnSpan(1),

                    Textarea::make('bio')
                        ->label(__('profile.bio'))
                        ->maxLength(500)->rows(3)
                        ->columnSpanFull(),

                    Toggle::make('show_files_publicly')
                        ->label(__('profile.show_files_publicly'))
                        ->helperText(__('profile.show_files_publicly_hint'))
                        ->columnSpanFull(),
                ]),

            // --- Website & social links --------------------------------
            Section::make(__('profile.section_links'))
                ->description(__('profile.section_links_hint'))
                ->icon('heroicon-o-link')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextInput::make('website')
                        ->label(__('profile.website'))
                        ->url()->prefixIcon('heroicon-m-globe-alt')->maxLength(255)
                        ->extraInputAttributes(['dir' => 'ltr']),

                    TextInput::make('twitter')
                        ->label(__('profile.twitter'))
                        ->prefix('@')->maxLength(50)
                        ->extraInputAttributes(['dir' => 'ltr']),

                    TextInput::make('github')
                        ->label(__('profile.github'))
                        ->prefix('@')->maxLength(50)
                        ->extraInputAttributes(['dir' => 'ltr']),
                ]),

            // --- Account & security ------------------------------------
            Section::make(__('profile.section_account'))
                ->description(__('profile.section_account_hint'))
                ->icon('heroicon-o-lock-closed')
                ->columns(2)
                ->schema([
                    $this->getEmailFormComponent()
                        ->prefixIcon('heroicon-m-envelope')
                        ->columnSpanFull(),
                    $this->getPasswordFormComponent()
                        ->prefixIcon('heroicon-m-key'),
                    $this->getPasswordConfirmationFormComponent()
                        ->prefixIcon('heroicon-m-key'),
                ]),
        ]);
    }

    /** Reserved-name guard — applied to members only; staff keep their names. */
    private function reservedRule(): array
    {
        return auth()->user()?->isStaff() ? [] : [new ReservedName];
    }

    /** Display-name guards (members only): reserved + must be a real, meaningful name. */
    private function nameRules(): array
    {
        return auth()->user()?->isStaff() ? [] : [new ReservedName, new MeaningfulName];
    }
}
