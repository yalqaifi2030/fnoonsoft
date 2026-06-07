<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Builds the data shown on the public maintenance page — shared by the
 * middleware (live) and the admin preview route so both stay in sync.
 */
class MaintenancePage
{
    /** @return array<string,mixed> */
    public static function data(): array
    {
        return [
            'title' => Setting::text('maintenance_title', __('site.maintenance.title')),
            'message' => Setting::text('maintenance_message', __('site.maintenance.message')),
            'until' => Setting::get('maintenance_until'),
            'siteName' => Setting::text('site_name', config('app.name')),
            'social' => array_filter([
                'twitter' => Setting::get('social_twitter'),
                'facebook' => Setting::get('social_facebook'),
                'instagram' => Setting::get('social_instagram'),
                'youtube' => Setting::get('social_youtube'),
                'github' => Setting::get('social_github'),
            ]),
            'email' => Setting::get('contact_email'),
        ];
    }
}
