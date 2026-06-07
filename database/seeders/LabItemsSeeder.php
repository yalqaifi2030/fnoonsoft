<?php

namespace Database\Seeders;

use App\Models\InteractiveLab;
use Illuminate\Database\Seeder;

class LabItemsSeeder extends Seeder
{
    public function run(): void
    {
        $byKey = InteractiveLab::all()->keyBy('key');

        $items = [
            // ---- Playground templates: data = {html, css, js} ----
            'playground' => [
                ['Counter app', 'تطبيق عدّاد', [
                    'html' => "<h1 id=\"n\">0</h1>\n<button onclick=\"inc()\">+1</button>\n<button onclick=\"dec()\">-1</button>",
                    'css' => "body{font-family:sans-serif;text-align:center;padding:30px}\n#n{font-size:64px;color:#006C35}\nbutton{font-size:20px;margin:6px;padding:8px 16px;border:0;border-radius:10px;background:#C9A961;cursor:pointer}",
                    'js' => "let c=0;const el=document.getElementById('n');\nfunction inc(){c++;el.textContent=c;console.log('count',c)}\nfunction dec(){c--;el.textContent=c;console.log('count',c)}",
                ]],
                ['Profile card', 'بطاقة تعريف', [
                    'html' => "<div class=\"card\"><div class=\"avatar\">FN</div><h3>Fnoon Student</h3><p>Computer Engineering</p></div>",
                    'css' => "body{display:grid;place-items:center;height:100vh;background:#f3f4f6;font-family:sans-serif}\n.card{background:#fff;padding:24px;border-radius:18px;box-shadow:0 10px 30px -10px rgba(0,0,0,.2);text-align:center}\n.avatar{width:64px;height:64px;border-radius:50%;background:#006C35;color:#fff;display:grid;place-items:center;font-weight:800;margin:0 auto 12px}",
                    'js' => "console.log('Card rendered');",
                ]],
                ['Canvas art', 'فن Canvas', [
                    'html' => "<canvas id=\"c\" width=\"300\" height=\"200\"></canvas>",
                    'css' => "body{display:grid;place-items:center;height:100vh;background:#0b1220}",
                    'js' => "const x=document.getElementById('c').getContext('2d');\nfor(let i=0;i<80;i++){x.fillStyle='hsl('+(i*4)+',80%,60%)';x.beginPath();x.arc(Math.random()*300,Math.random()*200,Math.random()*16,0,7);x.fill();}\nconsole.log('Drew 80 circles');",
                ]],
            ],
            // ---- Arduino sketches: data = {type, code, delay} ----
            'arduino' => [
                ['Blink', 'وميض', ['type' => 'blink', 'delay' => 500, 'code' => "void setup(){ pinMode(13,OUTPUT); }\nvoid loop(){\n  digitalWrite(13,HIGH); delay(500);\n  digitalWrite(13,LOW);  delay(500);\n}"]],
                ['Fade (PWM)', 'تلاشٍ', ['type' => 'fade', 'delay' => 40, 'code' => "void loop(){\n  for(int v=0;v<=255;v++) analogWrite(9,v);\n  for(int v=255;v>=0;v--) analogWrite(9,v);\n}"]],
                ['Traffic light', 'إشارة مرور', ['type' => 'traffic', 'delay' => 0, 'code' => "void loop(){\n  setLight(GREEN);  delay(3000);\n  setLight(YELLOW); delay(800);\n  setLight(RED);    delay(3000);\n}"]],
                ['SOS', 'استغاثة', ['type' => 'sos', 'delay' => 200, 'code' => "void loop(){\n  dot();dot();dot();\n  dash();dash();dash();\n  dot();dot();dot();\n  delay(2000);\n}"]],
            ],
            // ---- AI presets: data = {points, degree} ----
            'ai' => [
                ['Linear trend', 'اتجاه خطّي', ['degree' => 1, 'points' => '80,250; 160,220; 240,200; 320,170; 400,150; 480,130; 560,110; 640,90; 720,70']],
                ['Wave (poly)', 'موجة', ['degree' => 3, 'points' => '60,170; 140,90; 220,120; 300,220; 380,260; 460,200; 540,120; 620,150; 700,240']],
            ],
            // ---- Security interactions: each item = one tab. data = {type, icon, sample} ----
            'security' => [
                ['Caesar cipher', 'شيفرة قيصر', ['type' => 'caesar', 'icon' => 'fa-solid fa-right-left', 'sample' => 'Attack at dawn']],
                ['Password strength', 'قوة كلمة المرور', ['type' => 'password', 'icon' => 'fa-solid fa-key', 'sample' => 'P@ssw0rd!']],
                ['SHA-256 hash', 'تجزئة SHA-256', ['type' => 'hash', 'icon' => 'fa-solid fa-fingerprint', 'sample' => 'hello']],
                ['Base64 encoder', 'ترميز Base64', ['type' => 'base64', 'icon' => 'fa-solid fa-code', 'sample' => 'Hello Fnoon']],
                ['Brute-force time', 'زمن التخمين', ['type' => 'brute', 'icon' => 'fa-solid fa-stopwatch', 'sample' => 'qwerty']],
            ],
            // ---- Snippets: data = {category, lang, code} ----
            'snippets' => [
                ['Read a sensor', 'قراءة حسّاس', ['category' => 'arduino', 'lang' => 'cpp', 'code' => "int sensor = A0;\nvoid setup(){ Serial.begin(9600); }\nvoid loop(){\n  int v = analogRead(sensor);\n  Serial.println(v);\n  digitalWrite(13, v > 512 ? HIGH : LOW);\n  delay(200);\n}"]],
                ['Train a classifier', 'تدريب مصنّف', ['category' => 'ai', 'lang' => 'python', 'code' => "from sklearn.linear_model import LogisticRegression\nfrom sklearn.datasets import load_iris\nX, y = load_iris(return_X_y=True)\nmodel = LogisticRegression(max_iter=200).fit(X, y)\nprint('accuracy:', model.score(X, y))"]],
                ['Fetch an API', 'جلب من API', ['category' => 'js', 'lang' => 'javascript', 'code' => "async function getSoftware(){\n  const res = await fetch('/api/software');\n  const data = await res.json();\n  data.forEach(i => console.log(i.name));\n}\ngetSoftware();"]],
                ['Hash a password', 'تجزئة كلمة مرور', ['category' => 'security', 'lang' => 'python', 'code' => "import hashlib, os\nsalt = os.urandom(16)\nkey = hashlib.pbkdf2_hmac('sha256', b'secret', salt, 100_000)\nprint(salt.hex(), key.hex())"]],
            ],
        ];

        foreach ($items as $key => $list) {
            $lab = $byKey->get($key);
            if (! $lab || $lab->items()->exists()) {
                continue;
            }
            foreach ($list as $i => [$en, $ar, $data]) {
                $lab->items()->create([
                    'title' => ['en' => $en, 'ar' => $ar],
                    'data' => $data,
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }
        }
    }
}
