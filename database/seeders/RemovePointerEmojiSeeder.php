<?php

namespace Database\Seeders;

use App\Models\Software;
use Illuminate\Database\Seeder;

/**
 * One-off cleanup: remove the 👉 pointer emoji from every software description
 * (both ar & en), tidying the surrounding whitespace. Idempotent.
 */
class RemovePointerEmojiSeeder extends Seeder
{
    public function run(): void
    {
        $emoji = "\xF0\x9F\x91\x89"; // 👉
        $count = 0;

        Software::where('description', 'like', '%'.$emoji.'%')->get()->each(function (Software $s) use (&$count, $emoji) {
            $changed = false;

            foreach ($s->getTranslations('description') as $locale => $value) {
                if ($value === null || ! str_contains($value, $emoji)) {
                    continue;
                }
                // Remove the emoji (+ optional variation selector) and collapse the space around it.
                $clean = preg_replace('/\s*\x{1F449}\x{FE0F}?\s*/u', ' ', $value);
                $s->setTranslation('description', $locale, $clean);
                $changed = true;
            }

            if ($changed) {
                $s->save();
                $count++;
            }
        });

        echo "cleaned {$count} software descriptions\n";
    }
}
