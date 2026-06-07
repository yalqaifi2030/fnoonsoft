<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Developer;
use App\Models\DownloadLink;
use App\Models\Software;
use App\Models\SoftwareVersion;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'author@fnoon.test')->first();

        // --- Categories (one root per content type + a few children) -----
        $cats = [
            ['application', 'Browsers', 'المتصفحات', 'fa-solid fa-globe'],
            ['application', 'Multimedia', 'الوسائط المتعددة', 'fa-solid fa-photo-film'],
            ['application', 'Developer Tools', 'أدوات المطورين', 'fa-solid fa-terminal'],
            ['script', 'PHP Scripts', 'سكربتات PHP', 'fa-brands fa-php'],
            ['script', 'JavaScript', 'جافاسكربت', 'fa-brands fa-js'],
            ['template', 'Landing Pages', 'صفحات هبوط', 'fa-solid fa-rocket'],
            ['template', 'E-commerce', 'متاجر إلكترونية', 'fa-solid fa-cart-shopping'],
            ['plugin', 'WordPress', 'ووردبريس', 'fa-brands fa-wordpress'],
        ];

        $categoryModels = [];
        foreach ($cats as $i => [$type, $en, $ar, $icon]) {
            $categoryModels[] = Category::create([
                'name' => ['en' => $en, 'ar' => $ar],
                'slug' => Str::slug($en),
                'content_type' => $type,
                'icon' => $icon,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // --- Developers ---------------------------------------------------
        $devs = collect(['Aurora Labs', 'Nimbus Soft', 'CodeForge', 'PixelCraft', 'OpenStack Devs'])
            ->map(fn ($name) => Developer::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'website' => 'https://'.Str::slug($name).'.example.com',
                'description' => ['en' => "$name builds quality software.", 'ar' => "$name تبني برمجيات عالية الجودة."],
                'is_verified' => true,
            ]));

        // --- Tags ---------------------------------------------------------
        $tags = collect(['free', 'open-source', 'popular', 'new', 'editor-pick'])
            ->map(fn ($t) => Tag::create([
                'name' => ['en' => ucfirst($t), 'ar' => $t],
                'slug' => $t,
            ]));

        // --- Software items ----------------------------------------------
        $samples = [
            ['application', 'Aurora Browser', 'متصفح أورورا', 0, ['windows', 'macos', 'linux'], 'free'],
            ['application', 'Nimbus Video Editor', 'محرر نيمبوس للفيديو', 1, ['windows', 'macos'], 'trial'],
            ['application', 'ForgeIDE', 'فورج IDE', 2, ['windows', 'macos', 'linux'], 'open_source'],
            ['script', 'Invoice Manager PHP', 'مدير الفواتير PHP', 3, ['web'], 'paid'],
            ['script', 'Realtime Chat JS', 'دردشة فورية JS', 4, ['web'], 'open_source'],
            ['template', 'SaaS Landing Kit', 'قالب هبوط SaaS', 5, ['web'], 'paid'],
            ['template', 'ShopMax Store', 'متجر شوب ماكس', 6, ['web'], 'paid'],
            ['plugin', 'SEO Booster for WP', 'معزز SEO لووردبريس', 7, ['web'], 'free'],
        ];

        foreach ($samples as $idx => [$type, $en, $ar, $catIndex, $os, $license]) {
            $sw = Software::create([
                'content_type' => $type,
                'name' => ['en' => $en, 'ar' => $ar],
                'slug' => Str::slug($en),
                'short_description' => [
                    'en' => "$en — a polished, trusted $type for everyone.",
                    'ar' => "$en — أداة موثوقة ومصقولة للجميع.",
                ],
                'description' => [
                    'en' => "<p>$en is a high-quality $type. This is demo content seeded for development.</p>",
                    'ar' => "<p>$en هو $type عالي الجودة. هذا محتوى تجريبي لأغراض التطوير.</p>",
                ],
                'category_id' => $categoryModels[$catIndex]->id,
                'developer_id' => $devs[$idx % $devs->count()]->id,
                'user_id' => $author?->id,
                'current_version' => '1.'.$idx.'.0',
                'os_support' => $os,
                'license_type' => $license,
                'price' => $license === 'paid' ? 29.00 : null,
                'meta' => match ($type) {
                    'script' => ['programming_language' => 'PHP', 'framework' => 'Laravel'],
                    'template' => ['demo_url' => 'https://demo.example.com', 'framework' => 'Tailwind'],
                    'plugin' => ['platform' => 'WordPress'],
                    default => [],
                },
                'features' => [
                    ['en' => 'Blazing-fast performance, optimized for low resource usage', 'ar' => 'أداء فائق السرعة مع استهلاك منخفض للموارد'],
                    ['en' => 'Clean, modern and fully responsive interface', 'ar' => 'واجهة عصرية أنيقة ومتجاوبة بالكامل'],
                    ['en' => 'Cross-platform support out of the box', 'ar' => 'دعم متعدد المنصّات جاهز للاستخدام'],
                    ['en' => 'Regular security updates and malware-free guarantee', 'ar' => 'تحديثات أمنية دورية وخلوٌّ مضمون من البرمجيات الخبيثة'],
                    ['en' => 'Free lifetime updates and community support', 'ar' => 'تحديثات مجانية مدى الحياة ودعم مجتمعي'],
                    ['en' => 'Multi-language interface (Arabic & English)', 'ar' => 'واجهة متعددة اللغات (العربية والإنجليزية)'],
                ],
                'status' => 'published',
                'is_featured' => $idx % 3 === 0,
                'is_editor_choice' => $idx % 4 === 0,
                'is_malware_free' => true,
                'downloads_count' => random_int(500, 90000),
                'rating_avg' => round(random_int(35, 50) / 10, 1),
                'reviews_count' => random_int(2, 40),
                'published_at' => now()->subDays($idx),
            ]);

            $sw->tags()->attach($tags->random(random_int(1, 3))->pluck('id'));

            $version = SoftwareVersion::create([
                'software_id' => $sw->id,
                'version' => $sw->current_version,
                'changelog' => ['en' => 'Initial public release.', 'ar' => 'أول إصدار عام.'],
                'released_at' => now()->subDays($idx),
                'is_current' => true,
            ]);

            // Demo external mirror link (no R2 file needed for seeding)
            DownloadLink::create([
                'software_id' => $sw->id,
                'software_version_id' => $version->id,
                'label' => 'Official mirror',
                'type' => 'external',
                'os' => $os[0] ?? null,
                'external_url' => 'https://example.com/files/'.$sw->slug.'.zip',
                'original_filename' => $sw->slug.'.zip',
                'size_bytes' => random_int(5_000_000, 2_000_000_000),
                'is_active' => true,
            ]);
        }
    }
}
