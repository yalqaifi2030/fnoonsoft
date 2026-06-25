<?php

namespace Database\Seeders;

use App\Models\InteractiveLab;
use Illuminate\Database\Seeder;

/**
 * Five new, original "live" interactive labs spanning engineering, programming,
 * AI, cybersecurity and Arduino. Each `key` maps to a self-contained component
 * partial in resources/views/partials/labs/{key}.blade.php. Idempotent by key.
 */
class LiveLabsSeeder extends Seeder
{
    public function run(): void
    {
        $labs = [
            [
                'key' => 'gates',
                'slug' => 'logic-gates',
                'icon' => 'fa-solid fa-gears',
                'color' => 'from-cyan-500 to-blue-700',
                'title' => ['ar' => 'محاكي البوّابات المنطقية', 'en' => 'Logic Gates Simulator'],
                'description' => [
                    'ar' => 'بدّل المداخل حيًّا عبر بوّابات AND/OR/XOR/NAND… وشاهد المخرج وجدول الحقيقة فورًا — لطلّاب الهندسة والحاسب.',
                    'en' => 'Flip live inputs through AND/OR/XOR/NAND… gates and watch the output and truth table update instantly.',
                ],
            ],
            [
                'key' => 'pathfinding',
                'slug' => 'pathfinding',
                'icon' => 'fa-solid fa-route',
                'color' => 'from-teal-500 to-emerald-700',
                'title' => ['ar' => 'مستكشف المسارات', 'en' => 'Pathfinding Visualizer'],
                'description' => [
                    'ar' => 'ارسم الجدران على الشبكة وشاهد خوارزميات BFS و *A تبحث عن أقصر مسار خطوة بخطوة — لفهم الخوارزميات بصريًّا.',
                    'en' => 'Draw walls on a grid and watch BFS and A* search for the shortest path step by step.',
                ],
            ],
            [
                'key' => 'neural',
                'slug' => 'neural-network',
                'icon' => 'fa-solid fa-circle-nodes',
                'color' => 'from-fuchsia-500 to-purple-700',
                'title' => ['ar' => 'شبكة عصبية حيّة', 'en' => 'Live Neural Network'],
                'description' => [
                    'ar' => 'ضع نقاطًا من فئتين بالنقر، وشاهد خلية عصبية (Perceptron) تتعلّم الحدّ الفاصل بينها في الزمن الحقيقي.',
                    'en' => 'Place two classes of points and watch a perceptron learn the decision boundary in real time.',
                ],
            ],
            [
                'key' => 'password',
                'slug' => 'password-lab',
                'icon' => 'fa-solid fa-key',
                'color' => 'from-rose-500 to-red-700',
                'title' => ['ar' => 'مختبر كلمات المرور والتجزئة', 'en' => 'Password & Hashing Lab'],
                'description' => [
                    'ar' => 'قِس قوّة كلمة المرور وعدد بتات الإنتروبيا وزمن اختراقها المقدّر، واحسب بصمة SHA-256 حيًّا — للأمن السيبراني.',
                    'en' => 'Measure password strength, entropy bits and estimated crack time, and compute a live SHA-256 hash.',
                ],
            ],
            [
                'key' => 'sensors',
                'slug' => 'arduino-sensors',
                'icon' => 'fa-solid fa-microchip',
                'color' => 'from-sky-500 to-indigo-700',
                'title' => ['ar' => 'محاكي المستشعرات الحيّ', 'en' => 'Live Arduino Sensors'],
                'description' => [
                    'ar' => 'حرّك مستشعرات افتراضية (ضوء/حرارة/جهد) وشاهدها تتحكّم بمصباح PWM وسيرفو وإنذار، مع كود أردوينو المكافئ.',
                    'en' => 'Drive virtual sensors (light/heat/potentiometer) controlling a PWM LED, servo and buzzer, with equivalent Arduino code.',
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
                'sort_order' => 40 + $i,
                'is_active' => true,
            ]);
        }
    }
}
