<?php

namespace Database\Seeders;

use App\Models\InteractiveLab;
use Illuminate\Database\Seeder;

class InteractiveLabsSeeder extends Seeder
{
    public function run(): void
    {
        if (InteractiveLab::count() > 0) {
            return;
        }

        $labs = [
            ['playground', 'fa-solid fa-code', 'from-emerald-500 to-green-700',
                ['en' => 'Live code playground', 'ar' => 'ملعب الأكواد الحيّ'],
                ['en' => 'Write HTML, CSS & JavaScript with an instant preview and a real console.', 'ar' => 'اكتب HTML و CSS و JavaScript بمعاينة فورية وكونسول حقيقي.']],
            ['arduino', 'fa-solid fa-microchip', 'from-sky-500 to-indigo-700',
                ['en' => 'Arduino simulator', 'ar' => 'محاكي الأردوينو'],
                ['en' => 'Run real sketches — blink, fade, traffic lights and SOS — on a virtual board.', 'ar' => 'شغّل أكوادًا حقيقية — وميض، تلاشٍ، إشارة مرور و SOS — على لوحة افتراضية.']],
            ['ai', 'fa-solid fa-brain', 'from-fuchsia-500 to-purple-700',
                ['en' => 'AI playground', 'ar' => 'ملعب الذكاء الاصطناعي'],
                ['en' => 'Fit linear & polynomial models to your data and read R² / MSE live.', 'ar' => 'لائم نماذج خطّية ومتعدّدة الحدود لبياناتك واقرأ R² / MSE حيًّا.']],
            ['security', 'fa-solid fa-shield-halved', 'from-rose-500 to-red-700',
                ['en' => 'Cybersecurity lab', 'ar' => 'مختبر الأمن السيبراني'],
                ['en' => 'Ciphers, hashing (SHA-256), encoding and a brute-force time estimator.', 'ar' => 'تشفير، تجزئة (SHA-256)، ترميز، وتقدير زمن الاختراق بالقوة الغاشمة.']],
            ['snippets', 'fa-solid fa-book', 'from-amber-400 to-orange-600',
                ['en' => 'Code library', 'ar' => 'مكتبة الأكواد'],
                ['en' => 'Searchable, copy-ready snippets across every track.', 'ar' => 'مقتطفات قابلة للبحث والنسخ عبر كل المسارات.']],
        ];

        foreach ($labs as $i => [$key, $icon, $color, $title, $description]) {
            InteractiveLab::create([
                'key' => $key,
                'slug' => $key,
                'title' => $title,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }
}
