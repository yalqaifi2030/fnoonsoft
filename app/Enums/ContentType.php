<?php

namespace App\Enums;

enum ContentType: string
{
    case Application = 'application';
    case Script = 'script';
    case Template = 'template';
    case MobileApp = 'mobile_app';
    case Plugin = 'plugin';

    public function label(): string
    {
        return match ($this) {
            self::Application => __('content.type.application'),
            self::Script => __('content.type.script'),
            self::Template => __('content.type.template'),
            self::MobileApp => __('content.type.mobile_app'),
            self::Plugin => __('content.type.plugin'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Application => 'fa-solid fa-desktop',
            self::Script => 'fa-solid fa-code',
            self::Template => 'fa-solid fa-palette',
            self::MobileApp => 'fa-solid fa-mobile-screen-button',
            self::Plugin => 'fa-solid fa-puzzle-piece',
        };
    }

    /** @return array<string,string> value => label, for Filament selects */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
