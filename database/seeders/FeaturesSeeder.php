<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeaturesSeeder extends Seeder
{
    public function run(): void
    {
        if (Feature::count() > 0) {
            return;
        }

        $features = [
            ['fa-solid fa-shield-halved', ['en' => 'Verified & safe', 'ar' => 'موثّق وآمن'], ['en' => 'Every upload is checksum-verified and scanned before it goes live.', 'ar' => 'كل ملف يُفحص ويُولّد له checksum قبل نشره للجمهور.']],
            ['fa-solid fa-bolt', ['en' => 'Blazing fast', 'ar' => 'سريع جدًا'], ['en' => 'Served from a global CDN so downloads start instantly, anywhere.', 'ar' => 'يُخدَّم عبر CDN عالمي فيبدأ التحميل فورًا من أي مكان.']],
            ['fa-solid fa-database', ['en' => 'Built for big files', 'ar' => 'مهيّأ للملفات الكبيرة'], ['en' => 'Resumable engine handles single files up to 30 GB.', 'ar' => 'محرّك قابل للاستئناف يتحمّل ملفًا واحدًا حتى 30 جيجابايت.']],
            ['fa-solid fa-gem', ['en' => 'Curated quality', 'ar' => 'جودة منتقاة'], ['en' => 'Organised, searchable, editor-reviewed content.', 'ar' => 'محتوى منظّم وقابل للبحث ومراجَع.']],
        ];

        foreach ($features as $i => [$icon, $title, $description]) {
            Feature::create([
                'icon' => $icon,
                'title' => $title,
                'description' => $description,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }
}
