<?php

namespace Database\Seeders;

use App\Models\LearningCategory;
use Illuminate\Database\Seeder;

class LearningSeeder extends Seeder
{
    public function run(): void
    {
        if (LearningCategory::count() > 0) {
            return;
        }

        $data = [
            [
                'slug' => 'engineering',
                'name' => ['en' => 'Engineering & Programming', 'ar' => 'الهندسة والبرمجة'],
                'description' => ['en' => 'Web, software and engineering fundamentals.', 'ar' => 'أساسيات الويب والبرمجيات والهندسة.'],
                'icon' => 'fa-solid fa-code', 'color' => 'from-emerald-500 to-green-700',
                'videos' => [
                    ['HTML & CSS — full course', 'دورة HTML و CSS الكاملة', 'https://www.youtube.com/watch?v=G3e-cpL7ofc', '4:00:00', 'beginner'],
                    ['JavaScript for beginners', 'جافاسكربت للمبتدئين', 'https://www.youtube.com/watch?v=PkZNo7MFNFg', '3:26:00', 'beginner'],
                ],
            ],
            [
                'slug' => 'arduino',
                'name' => ['en' => 'Arduino & Electronics', 'ar' => 'الأردوينو والإلكترونيات'],
                'description' => ['en' => 'Build circuits and program microcontrollers.', 'ar' => 'ابنِ الدوائر وبرمج المتحكّمات الدقيقة.'],
                'icon' => 'fa-solid fa-microchip', 'color' => 'from-sky-500 to-indigo-700',
                'videos' => [
                    ['Arduino crash course', 'دورة الأردوينو المكثّفة', 'https://www.youtube.com/watch?v=BLrHTHUjPuw', '1:00:00', 'beginner'],
                ],
            ],
            [
                'slug' => 'ai',
                'name' => ['en' => 'Artificial Intelligence', 'ar' => 'الذكاء الاصطناعي'],
                'description' => ['en' => 'Machine learning and AI foundations.', 'ar' => 'أسس تعلّم الآلة والذكاء الاصطناعي.'],
                'icon' => 'fa-solid fa-brain', 'color' => 'from-fuchsia-500 to-purple-700',
                'videos' => [
                    ['Machine learning for everybody', 'تعلّم الآلة للجميع', 'https://www.youtube.com/watch?v=i_LwzRVP7bg', '3:53:00', 'intermediate'],
                ],
            ],
            [
                'slug' => 'cybersecurity',
                'name' => ['en' => 'Cybersecurity', 'ar' => 'الأمن السيبراني'],
                'description' => ['en' => 'Protect systems and understand attacks.', 'ar' => 'احمِ الأنظمة وافهم الهجمات.'],
                'icon' => 'fa-solid fa-shield-halved', 'color' => 'from-rose-500 to-red-700',
                'videos' => [
                    ['Cyber security full course', 'دورة الأمن السيبراني الكاملة', 'https://www.youtube.com/watch?v=U_P23SqJaDc', '4:00:00', 'beginner'],
                ],
            ],
        ];

        foreach ($data as $i => $cat) {
            $category = LearningCategory::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['description'],
                'icon' => $cat['icon'],
                'color' => $cat['color'],
                'sort_order' => $i,
                'is_active' => true,
            ]);

            foreach ($cat['videos'] as $j => [$en, $ar, $url, $duration, $level]) {
                $category->videos()->create([
                    'title' => ['en' => $en, 'ar' => $ar],
                    'url' => $url,
                    'duration' => $duration,
                    'level' => $level,
                    'sort_order' => $j,
                    'is_active' => true,
                ]);
            }
        }
    }
}
