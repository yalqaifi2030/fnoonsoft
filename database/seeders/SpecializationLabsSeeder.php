<?php

namespace Database\Seeders;

use App\Models\InteractiveLab;
use Illuminate\Database\Seeder;

/**
 * Four professional, in-browser interactive labs for university students across
 * specialisations. Each `key` maps to a self-contained component partial in
 * resources/views/partials/labs/{key}.blade.php. Idempotent by key/slug.
 */
class SpecializationLabsSeeder extends Seeder
{
    public function run(): void
    {
        $labs = [
            [
                'key' => 'circuits',
                'slug' => 'electric-circuits',
                'icon' => 'fa-solid fa-bolt',
                'color' => 'from-amber-500 to-orange-700',
                'title' => ['ar' => 'مختبر الدوائر الكهربائية', 'en' => 'Electric Circuits Lab'],
                'description' => [
                    'ar' => 'حاسبة قانون أوم، ومقاومة LED، والمقاومات على التوالي والتوازي — لطلّاب الهندسة الكهربائية والإلكترونيات.',
                    'en' => 'Ohm\'s law, LED resistor, and series/parallel resistance calculators — for electrical & electronics students.',
                ],
            ],
            [
                'key' => 'numbers',
                'slug' => 'number-systems',
                'icon' => 'fa-solid fa-calculator',
                'color' => 'from-blue-500 to-indigo-700',
                'title' => ['ar' => 'مختبر الأنظمة العددية والمنطق', 'en' => 'Number Systems & Logic Lab'],
                'description' => [
                    'ar' => 'تحويل فوري بين العشري والثنائي والسداسي والثماني، وعمليّات منطقية على المستوى البِتّي — لطلّاب الحاسب الآلي.',
                    'en' => 'Instant decimal/binary/hex/octal conversion and bitwise operations — for computer science students.',
                ],
            ],
            [
                'key' => 'plotter',
                'slug' => 'function-plotter',
                'icon' => 'fa-solid fa-chart-line',
                'color' => 'from-emerald-500 to-green-700',
                'title' => ['ar' => 'مختبر رسم الدوال', 'en' => 'Function Plotter Lab'],
                'description' => [
                    'ar' => 'ارسم أيّ دالّة رياضية y = f(x) على لوحة تفاعلية لحظيًّا — لطلّاب الرياضيات والهندسة.',
                    'en' => 'Plot any math function y = f(x) on an interactive canvas in real time — for math & engineering students.',
                ],
            ],
            [
                'key' => 'sorting',
                'slug' => 'sorting-visualizer',
                'icon' => 'fa-solid fa-arrow-down-wide-short',
                'color' => 'from-fuchsia-500 to-purple-700',
                'title' => ['ar' => 'مختبر محاكاة خوارزميات الترتيب', 'en' => 'Sorting Algorithms Visualizer'],
                'description' => [
                    'ar' => 'شاهد خوارزميات الترتيب (الفقاعي، الاختيار، الإدراج) تعمل أمامك خطوة بخطوة — لفهم الخوارزميات بصريًّا.',
                    'en' => 'Watch sorting algorithms (bubble, selection, insertion) run step by step — understand algorithms visually.',
                ],
            ],
        ];

        foreach ($labs as $i => $l) {
            if (InteractiveLab::where('key', $l['key'])->orWhere('slug', $l['slug'])->exists()) {
                continue;
            }

            InteractiveLab::create([
                'key' => $l['key'],
                'slug' => $l['slug'],
                'title' => $l['title'],
                'description' => $l['description'],
                'icon' => $l['icon'],
                'color' => $l['color'],
                'sort_order' => 30 + $i,
                'is_active' => true,
            ]);
        }
    }
}
