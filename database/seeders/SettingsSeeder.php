<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // --- Bilingual (JSON) settings ---
        $translatable = [
            'site_name' => ['en' => 'Fnoon', 'ar' => 'فنون'],
            'tagline' => [
                'en' => 'Download software, apps, scripts & website templates',
                'ar' => 'حمّل البرامج والتطبيقات والسكربتات وقوالب المواقع',
            ],
            'hero_title' => [
                'en' => 'Everything you need, ready to download',
                'ar' => 'كل ما تحتاجه، جاهز للتحميل',
            ],
            'hero_subtitle' => [
                'en' => 'A global library of trusted software, scripts and templates — fast, verified, malware-free.',
                'ar' => 'مكتبة عالمية من البرامج والسكربتات والقوالب الموثوقة — سريعة، موثّقة، خالية من الفيروسات.',
            ],
            'cta_title' => [
                'en' => 'Have something to share?',
                'ar' => 'لديك ما تشاركه؟',
            ],
            'cta_text' => [
                'en' => 'Upload your software, scripts or templates and reach a global audience.',
                'ar' => 'ارفع برامجك أو سكربتاتك أو قوالبك وصِل إلى جمهور عالمي.',
            ],
            'footer_about' => [
                'en' => 'Fnoon is a global platform to download software, apps, scripts and website templates — fast, verified and built for files up to 30 GB.',
                'ar' => 'فنون منصّة عالمية لتحميل البرامج والتطبيقات والسكربتات وقوالب المواقع — سريعة، موثّقة، ومهيّأة لملفات حتى 30 جيجابايت.',
            ],
        ];

        foreach ($translatable as $key => $value) {
            Setting::put($key, $value, 'json', 'site');
        }

        // --- Single-value settings ---
        $plain = [
            'contact_email' => 'support@fnoon.example.com',
            'contact_phone' => '+966 50 123 4567',
            'social_twitter' => 'https://twitter.com/fnoon',
            'social_facebook' => 'https://facebook.com/fnoon',
            'social_instagram' => 'https://instagram.com/fnoon',
            'social_youtube' => 'https://youtube.com/@fnoon',
            'social_github' => 'https://github.com/fnoon',
        ];

        foreach ($plain as $key => $value) {
            Setting::put($key, $value, 'string', 'site');
        }
    }
}
