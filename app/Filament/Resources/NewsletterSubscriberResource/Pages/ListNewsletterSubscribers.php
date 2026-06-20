<?php

namespace App\Filament\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Resources\NewsletterSubscriberResource;
use App\Jobs\SendNewsletter;
use App\Mail\NewsletterMail;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Mail;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        $auto = (bool) Setting::get('newsletter_auto_enabled', true);

        return [
            // Compose & send a campaign to all active subscribers.
            Actions\Action::make('compose')
                ->label(__('newsletter_admin.compose'))
                ->icon('heroicon-m-paper-airplane')
                ->color('primary')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel(__('newsletter_admin.send_now'))
                ->form([
                    Forms\Components\Placeholder::make('count')
                        ->label('')
                        ->content(fn () => __('newsletter_admin.will_send', ['n' => NewsletterSubscriber::active()->count()])),
                    Forms\Components\TextInput::make('subject')
                        ->label(__('newsletter_admin.subject'))->required()->maxLength(180),
                    Forms\Components\RichEditor::make('body')
                        ->label(__('newsletter_admin.body'))->required()
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'undo', 'redo']),
                    Forms\Components\TextInput::make('cta_url')
                        ->label(__('newsletter_admin.cta_url'))->url()->prefixIcon('heroicon-m-link')
                        ->extraInputAttributes(['dir' => 'ltr']),
                    Forms\Components\TextInput::make('cta_label')
                        ->label(__('newsletter_admin.cta_label'))->maxLength(60),
                ])
                ->action(function (array $data): void {
                    $count = NewsletterSubscriber::active()->count();

                    if ($count === 0) {
                        Notification::make()->warning()->title(__('newsletter_admin.none'))->send();

                        return;
                    }

                    SendNewsletter::dispatch(
                        $data['subject'],
                        $data['subject'],
                        $data['body'],
                        $data['cta_url'] ?? null,
                        ! empty($data['cta_url']) ? ($data['cta_label'] ?: __('newsletter.release_cta')) : null,
                    );

                    Notification::make()->success()
                        ->title(__('newsletter_admin.queued', ['n' => $count]))->send();
                }),

            // Send a sample to the logged-in admin to check branding + SMTP.
            Actions\Action::make('test')
                ->label(__('newsletter_admin.test'))
                ->icon('heroicon-m-beaker')->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn () => __('newsletter_admin.test_desc', ['email' => auth()->user()?->email]))
                ->action(function (): void {
                    $to = auth()->user()?->email;
                    if (! $to) {
                        Notification::make()->danger()->title(__('newsletter_admin.test_no_email'))->send();

                        return;
                    }

                    try {
                        Mail::to($to)->send(new NewsletterMail(
                            __('newsletter_admin.test_subject'),
                            __('newsletter_admin.test_subject'),
                            __('newsletter_admin.test_body'),
                            url('/'),
                            __('newsletter.release_cta'),
                            url('/'),
                        ));
                        Notification::make()->success()->title(__('newsletter_admin.test_ok', ['email' => $to]))->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()
                            ->title(__('newsletter_admin.test_fail'))->body($e->getMessage())->send();
                    }
                }),

            // Toggle the "auto-email on new release" behaviour.
            Actions\Action::make('toggle_auto')
                ->label($auto ? __('newsletter_admin.auto_on') : __('newsletter_admin.auto_off'))
                ->icon($auto ? 'heroicon-m-bell-alert' : 'heroicon-m-bell-slash')
                ->color($auto ? 'success' : 'gray')
                ->action(function (): void {
                    $new = ! (bool) Setting::get('newsletter_auto_enabled', true);
                    Setting::put('newsletter_auto_enabled', $new ? '1' : '0', 'boolean', 'newsletter');
                    Notification::make()->success()
                        ->title($new ? __('newsletter_admin.auto_enabled') : __('newsletter_admin.auto_disabled'))->send();
                }),
        ];
    }
}
