<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Software;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The "Mizan" Saudi law & judiciary Flutter app, with a live web preview.
 * updateOrCreate by slug — safe to re-run.
 */
class MizanLawProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = Category::where('slug', 'flutter')->value('id');
        $userId = User::orderBy('id')->value('id');

        Software::updateOrCreate(
            ['slug' => 'mizan-law'],
            [
                'content_type' => 'mobile_app',
                'category_id' => $categoryId,
                'user_id' => $userId,
                'name' => [
                    'ar' => 'ميزان — منصّة المحاماة والقضاء السعودي',
                    'en' => 'Mizan — Saudi Law & Judiciary Platform',
                ],
                'short_description' => [
                    'ar' => 'تطبيق عربيّ للمحامين والقانونيّين في السعودية — أنظمة وقضاء وأدوات قانونية في مكان واحد.',
                    'en' => 'An Arabic app for lawyers and legal professionals in Saudi Arabia — laws, judiciary and legal tools in one place.',
                ],
                'description' => [
                    'ar' => '<p>«ميزان» منصّة Flutter عربية موجّهة للمحامين والقانونيّين وطلّاب القانون في المملكة العربية السعودية: تصفّح الأنظمة واللوائح، أدوات قانونية مساعدة، ومرجع منظّم للقضاء السعودي — بواجهة عربية أنيقة وتجربة سلسة.</p><p>جرّب <strong>النسخة الحيّة</strong> أعلاه مباشرةً في متصفّحك دون تثبيت.</p>',
                    'en' => '<p>Mizan is an Arabic Flutter platform for lawyers, legal professionals and law students in Saudi Arabia: browse laws and regulations, helpful legal tools, and an organised reference for the Saudi judiciary — with a polished Arabic UI.</p><p>Try the <strong>live preview</strong> above right in your browser.</p>',
                ],
                'os_support' => ['android', 'ios'],
                'languages' => ['ar'],
                'license_type' => 'free',
                'current_version' => '1.0.0',
                'is_malware_free' => true,
                'is_featured' => true,
                'status' => 'published',
                'published_at' => Carbon::now(),
                'live_preview_url' => '/app-previews/app-lawyer/',
            ]
        );
    }
}
