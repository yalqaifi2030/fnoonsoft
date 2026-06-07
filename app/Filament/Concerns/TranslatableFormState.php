<?php

namespace App\Filament\Concerns;

/**
 * Spatie stores translatable columns as {locale: value} JSON. Filament fills a
 * single input with the whole array, which renders as "[object Object]". This
 * trait flattens each translatable field to the current locale's value when the
 * edit form loads, so admins edit plain text. Saving a string writes back to the
 * current locale and Spatie keeps the other locales untouched.
 *
 * Use on a resource's Edit page (CreateRecord starts empty, so it isn't needed).
 */
trait TranslatableFormState
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $model = static::getResource()::getModel();

        if (is_string($model) && method_exists($model, 'getTranslatableAttributes')) {
            $locale = app()->getLocale();

            foreach ((new $model)->getTranslatableAttributes() as $key) {
                if (array_key_exists($key, $data) && is_array($data[$key])) {
                    $data[$key] = $data[$key][$locale] ?? '';
                }
            }
        }

        return $data;
    }
}
