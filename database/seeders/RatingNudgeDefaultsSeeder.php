<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Sensible default text/timing for the rating-nudge toast (admin-editable). */
class RatingNudgeDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put('rating_nudge_enabled', '1', 'boolean', 'nudge');
        Setting::put('rating_nudge_title', ['ar' => 'أعجبك الموقع؟', 'en' => 'Enjoying the site?'], 'json', 'nudge');
        Setting::put('rating_nudge_message', ['ar' => 'قيّمنا — رأيك يسعدنا ويساعد غيرك 🌟', 'en' => 'Rate us — your feedback means a lot 🌟'], 'json', 'nudge');
        Setting::put('rating_nudge_cta_label', ['ar' => '', 'en' => ''], 'json', 'nudge');
        Setting::put('rating_nudge_cta_url', '', 'string', 'nudge');
        Setting::put('rating_nudge_delay', '6', 'string', 'nudge');
        Setting::put('rating_nudge_duration', '10', 'string', 'nudge');
    }
}
