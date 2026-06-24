<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * Professional "Why us" site features shown on the homepage band and managed
 * from Admin → Site features. Idempotent — skips a feature if one with the same
 * English title already exists, so it's safe to re-run and additive.
 */
class SiteFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'icon' => 'fa-solid fa-bolt',
                'en_title' => 'Lightning-fast downloads',
                'ar_title' => 'تحميل فائق السرعة',
                'en_desc' => 'An advanced engine for huge files with resume-on-disconnect — full speed, no waiting.',
                'ar_desc' => 'محرّك متطوّر يدعم الملفّات الضخمة والاستئناف عند انقطاع الاتصال — بأقصى سرعة ودون انتظار.',
            ],
            [
                'icon' => 'fa-solid fa-shield-halved',
                'en_title' => 'Safe & malware-free',
                'ar_title' => 'آمن وخالٍ من الفيروسات',
                'en_desc' => 'Every file is virus-scanned before publishing — download with complete peace of mind.',
                'ar_desc' => 'كلّ ملفّ يُفحَص ضدّ الفيروسات قبل النشر، لتحمّل بثقة وأمان تامّ.',
            ],
            [
                'icon' => 'fa-solid fa-cube',
                'en_title' => 'Interactive 3D preview',
                'ar_title' => 'معاينة ثلاثية الأبعاد',
                'en_desc' => 'View and interact with 3D models — rotate, zoom and AR — right before downloading.',
                'ar_desc' => 'شاهد النماذج ثلاثية الأبعاد وتفاعل معها (تدوير وتكبير وواقع معزّز) قبل التحميل.',
            ],
            [
                'icon' => 'fa-solid fa-microchip',
                'en_title' => 'Device compatibility check',
                'ar_title' => 'فاحص توافق جهازك',
                'en_desc' => 'Instantly see whether software runs on your device by matching your specs to its requirements.',
                'ar_desc' => 'اعرف فورًا هل يعمل البرنامج على جهازك بمقارنة مواصفاتك بمتطلّبات التشغيل.',
            ],
            [
                'icon' => 'fa-solid fa-graduation-cap',
                'en_title' => 'Learn & build',
                'ar_title' => 'تعلّم وابنِ',
                'en_desc' => 'A library of videos and interactive labs to learn and build skills right in your browser.',
                'ar_desc' => 'مكتبة فيديوهات ومختبرات تفاعلية تتعلّم بها وتبني مهاراتك مباشرةً في متصفّحك.',
            ],
            [
                'icon' => 'fa-solid fa-gift',
                'en_title' => 'Completely free',
                'ar_title' => 'مجّاني بالكامل',
                'en_desc' => 'Thousands of programs, templates, plugins and code — all free to download.',
                'ar_desc' => 'آلاف البرامج والقوالب والإضافات والأكواد — متاحة للتحميل مجّانًا.',
            ],
            [
                'icon' => 'fa-solid fa-cloud-arrow-down',
                'en_title' => 'Direct links & mirrors',
                'ar_title' => 'روابط مباشرة ومرايا',
                'en_desc' => 'Direct links, multiple mirrors and split parts for large files — reliable every time.',
                'ar_desc' => 'روابط تحميل مباشرة ومرايا متعدّدة وتقسيم للملفّات الكبيرة — تحميل موثوق دائمًا.',
            ],
            [
                'icon' => 'fa-solid fa-language',
                'en_title' => 'Arabic-first platform',
                'ar_title' => 'منصّة عربية أوّلًا',
                'en_desc' => 'A fully Arabic, professionally designed platform that also supports English.',
                'ar_desc' => 'منصّة عربية بالكامل بتصميم احترافي تدعم العربية والإنجليزية.',
            ],
        ];

        foreach ($features as $i => $f) {
            if (Feature::where('title->en', $f['en_title'])->exists()) {
                continue;
            }

            Feature::create([
                'icon' => $f['icon'],
                'title' => ['ar' => $f['ar_title'], 'en' => $f['en_title']],
                'description' => ['ar' => $f['ar_desc'], 'en' => $f['en_desc']],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
