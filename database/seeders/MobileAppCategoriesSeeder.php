<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Sub-categories for the new "Mobile App Templates" content type.
 * Idempotent by slug.
 */
class MobileAppCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'flutter',
                'icon' => 'fa-solid fa-feather-pointed',
                'name' => ['ar' => 'Flutter (فلاتر)', 'en' => 'Flutter'],
                'description' => ['ar' => 'قوالب ومشاريع Flutter بلغة Dart — متعدّدة المنصّات.', 'en' => 'Flutter (Dart) templates and projects — cross-platform.'],
            ],
            [
                'slug' => 'react-native',
                'icon' => 'fa-brands fa-react',
                'name' => ['ar' => 'React Native', 'en' => 'React Native'],
                'description' => ['ar' => 'قوالب React Native بلغة JavaScript / TypeScript.', 'en' => 'React Native templates in JavaScript / TypeScript.'],
            ],
            [
                'slug' => 'android-native',
                'icon' => 'fa-brands fa-android',
                'name' => ['ar' => 'أندرويد (Kotlin / Java)', 'en' => 'Android (Kotlin / Java)'],
                'description' => ['ar' => 'قوالب تطبيقات أندرويد الأصلية.', 'en' => 'Native Android app templates.'],
            ],
            [
                'slug' => 'ios-ui-kits',
                'icon' => 'fa-brands fa-apple',
                'name' => ['ar' => 'iOS وواجهات UI Kits', 'en' => 'iOS & UI Kits'],
                'description' => ['ar' => 'قوالب iOS الأصلية (Swift) وأطقم واجهات جاهزة.', 'en' => 'Native iOS (Swift) templates and ready UI kits.'],
            ],
        ];

        foreach ($categories as $i => $c) {
            if (Category::where('slug', $c['slug'])->exists()) {
                continue;
            }

            Category::create([
                'parent_id' => null,
                'content_type' => 'mobile_app',
                'slug' => $c['slug'],
                'name' => $c['name'],
                'description' => $c['description'],
                'icon' => $c['icon'],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
