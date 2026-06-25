<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Software;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The "Mizani" Flutter app product, with a live web preview.
 * updateOrCreate by slug — safe to re-run (and fixes any garbled text).
 */
class MizaniProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = Category::where('slug', 'flutter')->value('id');
        $userId = User::orderBy('id')->value('id');

        Software::updateOrCreate(
            ['slug' => 'mizani'],
            [
                'content_type' => 'mobile_app',
                'category_id' => $categoryId,
                'user_id' => $userId,
                'name' => [
                    'ar' => 'ميزاني — إدارة الميزانية الشخصية',
                    'en' => 'Mizani — Personal Budget Manager',
                ],
                'short_description' => [
                    'ar' => 'تطبيق عربيّ أنيق لإدارة الميزانية الشخصية وتتبّع المصروفات والأهداف — يعمل دون إنترنت.',
                    'en' => 'An elegant Arabic personal-budget app — track expenses, budgets and goals, fully offline.',
                ],
                'description' => [
                    'ar' => '<p>«ميزاني» تطبيق Flutter عربيّ لإدارة الميزانية الشخصية: تتبّع المصروفات والدخل، وميزانيات حسب الفئة، وأهداف ادّخار، وديون وأقساط، واشتراكات، مع تقارير ورسوم بيانية واضحة. يعمل دون إنترنت ويحفظ بياناتك على جهازك.</p><p>جرّب <strong>النسخة الحيّة</strong> أعلاه مباشرةً في متصفّحك دون أيّ تثبيت.</p>',
                    'en' => '<p>Mizani is an Arabic Flutter app for personal budgeting: track expenses and income, category budgets, savings goals, debts and installments, subscriptions, plus clear charts and reports. Works fully offline and stores data on your device.</p><p>Try the <strong>live preview</strong> above right in your browser.</p>',
                ],
                'os_support' => ['android', 'ios'],
                'languages' => ['ar', 'en'],
                'license_type' => 'free',
                'current_version' => '1.2.0',
                'is_malware_free' => true,
                'is_featured' => true,
                'status' => 'published',
                'published_at' => Carbon::now(),
                'live_preview_url' => '/app-previews/mizani/',
            ]
        );
    }
}
