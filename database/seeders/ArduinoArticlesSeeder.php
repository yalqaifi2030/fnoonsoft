<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Four original, hands-on articles on Arduino & electronics for makers and
 * students. Idempotent — skips any article whose slug already exists.
 */
class ArduinoArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->orderBy('id')->value('id');
        $categoryId = ArticleCategory::query()->orderBy('id')->value('id');

        foreach ($this->articles() as $i => $a) {
            if (Article::where('slug', $a['slug'])->exists()) {
                continue;
            }

            Article::create([
                'article_category_id' => $categoryId,
                'user_id' => $authorId,
                'slug' => $a['slug'],
                'title' => $a['title'],
                'excerpt' => $a['excerpt'],
                'body' => $a['body'],
                'meta_title' => $a['title'],
                'meta_description' => $a['excerpt'],
                'status' => 'published',
                'published_at' => Carbon::now()->subHours($i + 1),
                'views_count' => 0,
            ]);
        }
    }

    private function articles(): array
    {
        return [
            [
                'slug' => 'arduino-beginner-first-project-in-an-hour',
                'title' => [
                    'ar' => 'دليل المبتدئين لأردوينو: نفّذ أوّل مشروع خلال ساعة',
                    'en' => 'Arduino for Beginners: Build Your First Project in One Hour',
                ],
                'excerpt' => [
                    'ar' => 'لا تحتاج خلفية في الهندسة لتبدأ مع أردوينو. هذا الدليل يأخذك من فتح العلبة إلى أوّل مشروع يعمل بين يديك.',
                    'en' => 'You don\'t need an engineering background to start with Arduino. This guide takes you from unboxing to a working first project.',
                ],
                'body' => ['ar' => $this->a1ar(), 'en' => $this->a1en()],
            ],
            [
                'slug' => 'electronics-basics-every-arduino-beginner',
                'title' => [
                    'ar' => 'أساسيات الإلكترونيات التي يجب أن يعرفها كلّ مبتدئ في أردوينو',
                    'en' => 'Electronics Basics Every Arduino Beginner Must Know',
                ],
                'excerpt' => [
                    'ar' => 'الجهد والتيار والمقاومة وقانون أوم — المفاهيم التي تحميك من حرق مكوّناتك وتجعل مشاريعك تعمل من أوّل مرّة.',
                    'en' => 'Voltage, current, resistance and Ohm\'s law — the concepts that stop you from frying components and make projects work first time.',
                ],
                'body' => ['ar' => $this->a2ar(), 'en' => $this->a2en()],
            ],
            [
                'slug' => 'practical-arduino-projects-to-learn',
                'title' => [
                    'ar' => 'مشاريع أردوينو عملية تتعلّم منها خطوة بخطوة',
                    'en' => 'Practical Arduino Projects to Learn From, Step by Step',
                ],
                'excerpt' => [
                    'ar' => 'أفضل طريقة لإتقان أردوينو هي البناء. إليك مشاريع متدرّجة من السهل إلى المتوسّط، كلّ مشروع يعلّمك مهارة جديدة.',
                    'en' => 'The best way to master Arduino is to build. Here are graded projects from easy to intermediate, each teaching a new skill.',
                ],
                'body' => ['ar' => $this->a3ar(), 'en' => $this->a3en()],
            ],
            [
                'slug' => 'from-arduino-to-iot-esp32',
                'title' => [
                    'ar' => 'من أردوينو إلى إنترنت الأشياء (IoT): ابدأ مع ESP32',
                    'en' => 'From Arduino to IoT: Getting Started with the ESP32',
                ],
                'excerpt' => [
                    'ar' => 'حين تتقن أردوينو، الخطوة التالية هي وصل مشاريعك بالإنترنت. تعرّف على ESP32 وكيف تبني أوّل مشروع إنترنت أشياء.',
                    'en' => 'Once you master Arduino, the next step is connecting projects to the internet. Meet the ESP32 and build your first IoT project.',
                ],
                'body' => ['ar' => $this->a4ar(), 'en' => $this->a4en()],
            ],
        ];
    }

    private function a1ar(): string
    {
        return <<<'HTML'
<p>أردوينو هو أسهل بوّابة لدخول عالم الإلكترونيات والبرمجة الفيزيائية. إنّه <strong>لوحة تحكّم دقيقة (Microcontroller) قابلة للبرمجة</strong> تقرأ مدخلات (أزرار، مستشعرات) وتتحكّم بمخرجات (مصابيح، محرّكات). والأجمل: تبدأ دون خبرة سابقة.</p>
<h2>ما الذي تحتاجه للبداية؟</h2>
<ul>
<li>لوحة <strong>Arduino Uno</strong> (الأشهر للمبتدئين).</li>
<li>كابل USB لتوصيلها بالحاسوب.</li>
<li>مصباح <strong>LED</strong> ومقاومة <strong>220 أوم</strong>.</li>
<li>لوحة تجارب (<strong>Breadboard</strong>) وأسلاك توصيل.</li>
</ul>
<h2>الخطوة 1: ثبّت بيئة Arduino IDE</h2>
<p>حمّل برنامج <strong>Arduino IDE</strong> مجّانًا من الموقع الرسمي، ثبّته، ووصّل اللوحة بالـUSB. من القائمة اختر نوع اللوحة (Arduino Uno) والمنفذ (Port). أصبحت جاهزًا.</p>
<h2>الخطوة 2: مشروعك الأوّل — تشغيل مصباح (Blink)</h2>
<p>المشروع الكلاسيكي: جعل مصباح يومض. وصّل القدم الطويلة للـLED إلى الطرف 13 عبر المقاومة، والقدم القصيرة إلى GND. ثمّ اكتب هذا الكود:</p>
<pre><code>void setup() {
  pinMode(13, OUTPUT);   // اجعل الطرف 13 مخرجًا
}

void loop() {
  digitalWrite(13, HIGH); // أشعل المصباح
  delay(1000);            // انتظر ثانية
  digitalWrite(13, LOW);  // أطفئ المصباح
  delay(1000);            // انتظر ثانية
}</code></pre>
<p>اضغط زرّ الرفع (Upload)، وستجد مصباحك يومض كلّ ثانية. مبروك — أنت الآن تبرمج العتاد!</p>
<h2>الخطوة 3: افهم ما كتبت</h2>
<ul>
<li><code>setup()</code>: تُنفَّذ مرّة واحدة عند التشغيل — للإعدادات.</li>
<li><code>loop()</code>: تتكرّر إلى ما لا نهاية — قلب البرنامج.</li>
<li><code>pinMode()</code>: تحدّد إن كان الطرف مدخلًا أو مخرجًا.</li>
<li><code>digitalWrite()</code>: ترسل جهدًا (HIGH) أو لا (LOW).</li>
<li><code>delay()</code>: توقّف بالميلي ثانية.</li>
</ul>
<h2>الخطوة التالية</h2>
<p>عدّل قيمة <code>delay</code> لتغيير السرعة، ثمّ جرّب إضافة <strong>زرّ</strong> يتحكّم بالمصباح، أو <strong>مستشعر إضاءة</strong>. كلّ تعديل صغير يعلّمك مفهومًا جديدًا.</p>
<h2>نصائح للمبتدئ</h2>
<ul>
<li>لا تخف من تجربة التوصيلات — أردوينو متسامح، لكن تحقّق دائمًا من GND والجهد.</li>
<li>المقاومة مع LED <strong>إلزامية</strong> وإلّا احترق المصباح.</li>
<li>ابدأ بسيطًا، ولا تنتقل لمشروع أكبر قبل أن تفهم الحالي.</li>
</ul>
<h2>الخلاصة</h2>
<p>خلال ساعة انتقلت من الصفر إلى مشروع يعمل. أردوينو يكافئ التجربة، وكلّ مشروع يفتح بابًا لما بعده. الخطوة التالية: تعلّم أساسيات الإلكترونيات لتبني مشاريع أعقد بثقة.</p>
HTML;
    }

    private function a1en(): string
    {
        return <<<'HTML'
<p>Arduino is the easiest gateway into electronics and physical computing — a programmable microcontroller board that reads inputs (buttons, sensors) and drives outputs (LEDs, motors). Best of all, you start with zero experience.</p>
<h2>What you need</h2>
<ul>
<li>An <strong>Arduino Uno</strong> board, a USB cable, an <strong>LED</strong>, a <strong>220Ω resistor</strong>, a breadboard and jumper wires.</li>
</ul>
<h2>Step 1: install the Arduino IDE</h2>
<p>Download the free <strong>Arduino IDE</strong>, install it, connect the board via USB, and select the board (Arduino Uno) and port.</p>
<h2>Step 2: your first project — Blink an LED</h2>
<p>Wire the LED's long leg to pin 13 through the resistor, and the short leg to GND. Then upload:</p>
<pre><code>void setup() {
  pinMode(13, OUTPUT);
}

void loop() {
  digitalWrite(13, HIGH);
  delay(1000);
  digitalWrite(13, LOW);
  delay(1000);
}</code></pre>
<p>Press Upload and your LED blinks every second. You are now programming hardware!</p>
<h2>Step 3: understand the code</h2>
<ul>
<li><code>setup()</code>: runs once at start — for configuration.</li>
<li><code>loop()</code>: repeats forever — the heart of the program.</li>
<li><code>pinMode()</code>, <code>digitalWrite()</code>, <code>delay()</code>: configure a pin, send HIGH/LOW, and pause.</li>
</ul>
<h2>Tips for beginners</h2>
<ul>
<li>An LED always needs a resistor or it burns out.</li>
<li>Always check GND and voltage.</li>
<li>Start simple; understand the current project before the next.</li>
</ul>
<h2>Conclusion</h2>
<p>In an hour you went from zero to a working project. Next, learn the electronics basics to build more complex projects with confidence.</p>
HTML;
    }

    private function a2ar(): string
    {
        return <<<'HTML'
<p>قبل أن تبني مشاريع أعقد، تحتاج فهمًا بسيطًا لثلاثة مفاهيم تحكم كلّ دائرة كهربائية. هذه الأساسيات تحميك من <strong>حرق مكوّناتك</strong> وتجعل مشاريعك تعمل من أوّل مرّة.</p>
<h2>الجهد والتيار والمقاومة — بتشبيه الماء</h2>
<p>تخيّل أنبوب ماء:</p>
<ul>
<li><strong>الجهد (Voltage)</strong>: ضغط الماء الذي يدفعه — يُقاس بالفولت (V).</li>
<li><strong>التيار (Current)</strong>: كمية الماء المتدفّقة — يُقاس بالأمبير (A).</li>
<li><strong>المقاومة (Resistance)</strong>: ضيق الأنبوب الذي يعيق التدفّق — تُقاس بالأوم (Ω).</li>
</ul>
<h2>قانون أوم: المعادلة التي تحكم كلّ شيء</h2>
<blockquote>الجهد = التيار × المقاومة &nbsp;&nbsp;(V = I × R)</blockquote>
<p>بهذه المعادلة البسيطة تحسب أيّ قيمة إذا عرفت الاثنتين الأخريين. إنّها أهمّ معادلة في الإلكترونيات.</p>
<h2>لماذا يحتاج LED مقاومة؟ (وكيف تحسبها)</h2>
<p>المصباح LED يسحب تيارًا كبيرًا إن وُصِل مباشرةً فيحترق. المقاومة تحدّ التيار. الحساب:</p>
<pre><code>R = (جهد المصدر − جهد LED) / التيار المطلوب
R = (5V − 2V) / 0.02A = 150Ω</code></pre>
<p>لذلك نستخدم مقاومة 220Ω (الأقرب الأكبر، آمنة). دائمًا اختر مقاومة أكبر قليلًا للأمان.</p>
<h2>قراءة المقاومات بالألوان</h2>
<p>المقاومات تحمل شرائط ملوّنة ترمز لقيمتها. مثلًا مقاومة 220Ω: أحمر-أحمر-بنّي. تطبيقات الجوال تقرأها لك، لكن من المفيد فهم المبدأ.</p>
<h2>لوحة التجارب (Breadboard)</h2>
<p>تتيح بناء الدوائر دون لحام. تذكّر: الأعمدة الجانبية (+ و−) متّصلة طوليًّا للتغذية، والصفوف الوسطى متّصلة أفقيًّا لكلّ مكوّن. افهم اتصالاتها الداخلية لتتجنّب أخطاء التوصيل.</p>
<h2>المكثّفات باختصار</h2>
<p>المكثّف (Capacitor) يخزّن شحنة ويُنعّم تذبذبات الجهد. ستستخدمه لاحقًا لتثبيت تغذية المستشعرات أو فلترة الضوضاء.</p>
<h2>أداتك الأهمّ: الملتيميتر (Multimeter)</h2>
<p>جهاز رخيص يقيس الجهد والتيار والمقاومة، ويكشف أين تنقطع دائرتك. لا تبدأ مشاريع جادّة بدونه — سيوفّر عليك ساعات من الحيرة.</p>
<h2>الخلاصة</h2>
<p>الجهد والتيار والمقاومة وقانون أوم هي خريطتك في كلّ دائرة. افهمها مرّة، وستتوقّف عن حرق المكوّنات وتبدأ ببناء مشاريع تعمل بثقة. الإلكترونيات منطقيّة — وليست سحرًا.</p>
HTML;
    }

    private function a2en(): string
    {
        return <<<'HTML'
<p>Before complex projects, you need a simple grasp of three concepts that govern every circuit. These basics stop you from frying components and make projects work first time.</p>
<h2>Voltage, current and resistance — the water analogy</h2>
<ul>
<li><strong>Voltage</strong>: the pressure pushing the water — in volts (V).</li>
<li><strong>Current</strong>: how much water flows — in amps (A).</li>
<li><strong>Resistance</strong>: how narrow the pipe is — in ohms (Ω).</li>
</ul>
<h2>Ohm's law: the equation that governs everything</h2>
<blockquote>Voltage = Current × Resistance &nbsp; (V = I × R)</blockquote>
<p>With this you can find any value from the other two. It is the most important equation in electronics.</p>
<h2>Why an LED needs a resistor (and how to size it)</h2>
<pre><code>R = (Source voltage − LED voltage) / desired current
R = (5V − 2V) / 0.02A = 150Ω</code></pre>
<p>So we use a 220Ω resistor (the nearest larger, safe value). Always pick slightly larger for safety.</p>
<h2>The breadboard</h2>
<p>Build circuits without soldering. Side rails (+/−) run vertically for power; middle rows run horizontally per component. Understand the internal connections to avoid wiring mistakes.</p>
<h2>Capacitors, briefly</h2>
<p>A capacitor stores charge and smooths voltage ripples — useful for stabilizing sensor power and filtering noise.</p>
<h2>Your key tool: the multimeter</h2>
<p>A cheap device that measures voltage, current and resistance, and finds where your circuit breaks. Don't start serious projects without one.</p>
<h2>Conclusion</h2>
<p>Voltage, current, resistance and Ohm's law are your map for every circuit. Learn them once and stop frying components. Electronics is logical — not magic.</p>
HTML;
    }

    private function a3ar(): string
    {
        return <<<'HTML'
<p>قراءة النظريّات وحدها لا تصنع مهندسًا. <strong>أردوينو يُتقَن بالبناء.</strong> إليك مشاريع متدرّجة، كلّ مشروع يضيف مهارة ومكوّنًا جديدًا — ابنِها بالترتيب.</p>
<h2>لماذا التعلّم بالمشاريع؟</h2>
<p>كلّ مشروع يضعك أمام مشكلة حقيقية: توصيل، كود، وتصحيح خطأ. هذه الدورة (بناء ← خطأ ← حلّ) هي التي تبني الخبرة فعلًا.</p>
<h2>المستوى السهل</h2>
<ul>
<li><strong>1. إشارة مرور بثلاثة مصابيح</strong>: تتعلّم التحكّم بعدّة مخارج وتسلسل التوقيت.</li>
<li><strong>2. زرّ يتحكّم بمصباح</strong>: تتعلّم قراءة المدخلات الرقمية وحالة الزرّ.</li>
<li><strong>3. مستشعر إضاءة (LDR)</strong>: مصباح يضيء تلقائيًّا في الظلام — أوّل لقاء مع المدخلات التماثلية (Analog).</li>
</ul>
<h2>المستوى المتوسّط</h2>
<ul>
<li><strong>4. محطّة طقس (DHT11)</strong>: اقرأ الحرارة والرطوبة واعرضها — تتعلّم التعامل مع مستشعر رقمي ومكتباته.</li>
<li><strong>5. مقياس مسافة (HC-SR04)</strong>: مستشعر فوق صوتي يقيس المسافة — أساس مشاريع الروبوت وركن السيّارة.</li>
<li><strong>6. شاشة LCD</strong>: اعرض بيانات مشاريعك على شاشة بدل الحاسوب — تتعلّم بروتوكول I2C.</li>
<li><strong>7. التحكّم بمحرّك سيرفو</strong>: حرّك ذراعًا أو بوّابة بزاوية دقيقة — أساس الروبوتات.</li>
</ul>
<h2>مشاريع تجمع كلّ ما تعلّمت</h2>
<ul>
<li><strong>8. نظام إنذار</strong>: مستشعر حركة PIR + صفّارة + LED.</li>
<li><strong>9. حوض/نبتة ذكية</strong>: مستشعر رطوبة تربة + مضخّة ماء صغيرة.</li>
<li><strong>10. سيّارة روبوت</strong>: محرّكات + مستشعر مسافة لتفادي العوائق.</li>
</ul>
<h2>نصائح لتنفيذ ناجح</h2>
<ul>
<li>ارسم مخطّط التوصيل على ورقة قبل البناء (استخدم برنامج <strong>Tinkercad</strong> للمحاكاة مجّانًا).</li>
<li>اختبر كلّ مكوّن وحده قبل دمج المشروع كاملًا.</li>
<li>علّق على كودك بالعربية لتفهمه لاحقًا.</li>
</ul>
<h2>الخلاصة</h2>
<p>ابدأ من المستوى السهل ولا تقفز. كلّ مشروع تُتقنه يفتح لك القدرة على دمج مكوّنات أكثر. بعد هذه العشرة، ستكون جاهزًا للخطوة الكبرى: ربط مشاريعك بالإنترنت عبر إنترنت الأشياء.</p>
HTML;
    }

    private function a3en(): string
    {
        return <<<'HTML'
<p>Reading theory alone won't make an engineer. <strong>Arduino is mastered by building.</strong> Here are graded projects, each adding a new skill and component — build them in order.</p>
<h2>Why project-based learning?</h2>
<p>Each project puts you in front of a real problem: wiring, code, and debugging. This build → error → fix loop is what truly builds experience.</p>
<h2>Easy level</h2>
<ul>
<li><strong>1. Traffic light</strong>: control several outputs and timing sequences.</li>
<li><strong>2. Button-controlled LED</strong>: read digital inputs and button state.</li>
<li><strong>3. Light sensor (LDR)</strong>: a lamp that turns on in the dark — your first analog input.</li>
</ul>
<h2>Intermediate level</h2>
<ul>
<li><strong>4. Weather station (DHT11)</strong>: read temperature and humidity and display them.</li>
<li><strong>5. Distance meter (HC-SR04)</strong>: an ultrasonic sensor — the basis of robotics and parking aids.</li>
<li><strong>6. LCD display</strong>: show data on a screen — learn the I2C protocol.</li>
<li><strong>7. Servo motor control</strong>: move an arm or gate to a precise angle.</li>
</ul>
<h2>Projects that combine everything</h2>
<ul>
<li><strong>8. Alarm system</strong>: PIR motion sensor + buzzer + LED.</li>
<li><strong>9. Smart plant</strong>: soil-moisture sensor + small water pump.</li>
<li><strong>10. Robot car</strong>: motors + distance sensor for obstacle avoidance.</li>
</ul>
<h2>Tips for success</h2>
<ul>
<li>Sketch the wiring on paper first (use <strong>Tinkercad</strong> to simulate for free).</li>
<li>Test each component alone before combining.</li>
<li>Comment your code so you understand it later.</li>
</ul>
<h2>Conclusion</h2>
<p>Start easy and don't skip. After these ten, you'll be ready for the big step: connecting projects to the internet with IoT.</p>
HTML;
    }

    private function a4ar(): string
    {
        return <<<'HTML'
<p>حين تتقن أردوينو وتبني مشاريعك، يأتي السؤال الطبيعي: «كيف أتحكّم بها عن بُعد أو أرسل بياناتها للإنترنت؟». هذا هو عالم <strong>إنترنت الأشياء (IoT)</strong>، وبوّابتك إليه هي لوحة <strong>ESP32</strong>.</p>
<h2>ما هو إنترنت الأشياء؟</h2>
<p>هو ربط الأجهزة الفيزيائية بالإنترنت لتبادل البيانات: مستشعر حرارة يرسل قراءاته لهاتفك، أو مصباح تتحكّم به من أيّ مكان. أجهزة تتحدّث، وبيانات تتدفّق.</p>
<h2>لماذا ESP32 وليس أردوينو Uno؟</h2>
<ul>
<li><strong>واي فاي وبلوتوث مدمجان</strong> — لا تحتاج وحدات إضافية.</li>
<li><strong>أقوى بكثير</strong>: معالج ثنائي النواة وذاكرة أكبر.</li>
<li><strong>رخيص</strong>: غالبًا أرخص من Uno مع كلّ هذه الإمكانات.</li>
<li>يُبرمَج بنفس بيئة <strong>Arduino IDE</strong> — انتقالك سلس.</li>
</ul>
<h2>أوّل مشروع IoT: أرسل قراءة مستشعر للإنترنت</h2>
<p>الفكرة: ESP32 يقرأ مستشعر حرارة، ويتّصل بالواي فاي، ويرسل القراءة لمنصّة سحابية تعرضها على هاتفك. الكود يبدأ هكذا:</p>
<pre><code>#include &lt;WiFi.h&gt;

void setup() {
  Serial.begin(115200);
  WiFi.begin("اسم_الشبكة", "كلمة_المرور");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("تمّ الاتصال بالواي فاي!");
}</code></pre>
<p>بعد الاتصال، ترسل البيانات عبر بروتوكول مثل HTTP أو MQTT إلى السحابة.</p>
<h2>منصّات تبدأ بها مجّانًا</h2>
<ul>
<li><strong>Blynk</strong>: تطبيق جوال تبني به لوحة تحكّم بالسحب والإفلات — الأسهل للمبتدئين.</li>
<li><strong>ThingSpeak</strong>: لتخزين البيانات ورسمها بيانيًّا.</li>
<li><strong>Firebase</strong>: قاعدة بيانات لحظية من جوجل.</li>
<li><strong>MQTT</strong>: بروتوكول خفيف هو معيار صناعة إنترنت الأشياء.</li>
</ul>
<h2>أفكار مشاريع IoT</h2>
<ul>
<li>محطّة طقس ترسل البيانات لهاتفك من أيّ مكان.</li>
<li>نظام ريّ ذكي يسقي نباتك تلقائيًّا ويُعلمك.</li>
<li>قفل باب أو إضاءة منزل تتحكّم بها عن بُعد.</li>
<li>عدّاد كهرباء/ماء ذكيّ يراقب استهلاكك.</li>
</ul>
<h2>تنبيه أمنيّ مهمّ</h2>
<blockquote>لا تكتب كلمات مرور الواي فاي أو مفاتيح المنصّات داخل كود تنشره علنًا (على GitHub مثلًا). استخدم ملفّ إعدادات منفصلًا، وفعّل التشفير حيثما أمكن.</blockquote>
<h2>الخلاصة</h2>
<p>ESP32 يحوّل مشاريعك من «تعمل على الطاولة» إلى «متّصلة بالعالم». إنترنت الأشياء مجالٌ ضخم ومطلوب في سوق العمل، وأنت تملك الآن نقطة البداية. اختر مشروعًا بسيطًا، وصِله بالإنترنت، وستفتح أمامك آفاق لا تنتهي.</p>
HTML;
    }

    private function a4en(): string
    {
        return <<<'HTML'
<p>Once you master Arduino, the natural question is: "How do I control my projects remotely or send their data to the internet?" That's the world of <strong>IoT</strong>, and your gateway is the <strong>ESP32</strong>.</p>
<h2>What is IoT?</h2>
<p>Connecting physical devices to the internet to exchange data: a temperature sensor sending readings to your phone, or a lamp you control from anywhere.</p>
<h2>Why ESP32, not Arduino Uno?</h2>
<ul>
<li><strong>Built-in Wi-Fi and Bluetooth</strong> — no extra modules.</li>
<li><strong>Far more powerful</strong>: dual-core CPU and more memory.</li>
<li><strong>Cheap</strong>, and programmed with the same <strong>Arduino IDE</strong>.</li>
</ul>
<h2>First IoT project: send a sensor reading to the internet</h2>
<pre><code>#include &lt;WiFi.h&gt;

void setup() {
  Serial.begin(115200);
  WiFi.begin("SSID", "PASSWORD");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("Wi-Fi connected!");
}</code></pre>
<p>After connecting, send data via HTTP or MQTT to the cloud.</p>
<h2>Free platforms to start with</h2>
<ul>
<li><strong>Blynk</strong>: a drag-and-drop mobile dashboard — easiest for beginners.</li>
<li><strong>ThingSpeak</strong>: store and chart data.</li>
<li><strong>Firebase</strong>: Google's realtime database.</li>
<li><strong>MQTT</strong>: the lightweight industry-standard IoT protocol.</li>
</ul>
<h2>Project ideas</h2>
<ul>
<li>A weather station sending data to your phone from anywhere.</li>
<li>A smart irrigation system that waters plants automatically.</li>
<li>Remote-controlled home lighting or a door lock.</li>
</ul>
<h2>Important security note</h2>
<blockquote>Never put Wi-Fi passwords or platform keys in code you publish publicly (e.g. on GitHub). Use a separate config file.</blockquote>
<h2>Conclusion</h2>
<p>The ESP32 turns your projects from "works on the bench" to "connected to the world." IoT is a huge, in-demand field — and you now have your starting point.</p>
HTML;
    }
}
