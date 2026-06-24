<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Four original, student-focused articles on software development & AI.
 * Idempotent — skips any article whose slug already exists.
 */
class StudentArticlesSeeder extends Seeder
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
                'published_at' => Carbon::now()->subDays($i),
                'views_count' => 0,
            ]);
        }
    }

    private function articles(): array
    {
        return [
            [
                'slug' => 'programming-roadmap-university-students',
                'title' => [
                    'ar' => 'خارطة طريق تعلّم البرمجة لطالب الجامعة: من الصفر إلى أوّل مشروع حقيقي',
                    'en' => 'A Programming Roadmap for University Students: From Zero to Your First Real Project',
                ],
                'excerpt' => [
                    'ar' => 'دليل عملي مرتّب يأخذك من الحيرة في البداية إلى بناء أوّل مشروع تفخر به — مصمّم خصّيصًا لطالب الجامعة المشغول.',
                    'en' => 'A practical, ordered guide that takes you from confusion to a project you are proud of — built for the busy university student.',
                ],
                'body' => ['ar' => $this->a1ar(), 'en' => $this->a1en()],
            ],
            [
                'slug' => 'ai-for-students-smart-not-lazy',
                'title' => [
                    'ar' => 'كيف يستخدم طالب الجامعة الذكاء الاصطناعي بذكاء — لا كسلًا',
                    'en' => 'How University Students Use AI Smartly — Not Lazily',
                ],
                'excerpt' => [
                    'ar' => 'الذكاء الاصطناعي يضاعف إنتاجيّتك إن استخدمته مساعدًا، ويُضعف مهاراتك إن جعلته يفكّر بدلًا عنك. إليك الطريقة الصحيحة.',
                    'en' => 'AI multiplies your output when used as an assistant, and erodes your skills when it thinks for you. Here is the right way.',
                ],
                'body' => ['ar' => $this->a2ar(), 'en' => $this->a2en()],
            ],
            [
                'slug' => 'professional-graduation-project-guide',
                'title' => [
                    'ar' => 'مشروع التخرّج الاحترافي: دليل عملي من الفكرة إلى التسليم',
                    'en' => 'The Professional Graduation Project: A Practical Guide from Idea to Delivery',
                ],
                'excerpt' => [
                    'ar' => 'معظم مشاريع التخرّج تفشل في الإدارة لا في الفكرة. هذا الدليل يرتّب لك الطريق خطوة بخطوة لمشروع يلفت الأنظار.',
                    'en' => 'Most graduation projects fail in management, not in the idea. This guide orders the path step by step for a standout project.',
                ],
                'body' => ['ar' => $this->a3ar(), 'en' => $this->a3en()],
            ],
            [
                'slug' => 'software-skills-university-doesnt-teach',
                'title' => [
                    'ar' => '٧ مهارات هندسة برمجيات لا تُدرّسها الجامعة لكنّها تصنع الفرق في سوق العمل',
                    'en' => '7 Software Engineering Skills University Does Not Teach — But the Job Market Demands',
                ],
                'excerpt' => [
                    'ar' => 'الفجوة بين الخرّيج والمحترف ليست في الخوارزميات، بل في هذه المهارات العملية التي يتعلّمها الجميع متأخّرًا. تعلّمها مبكّرًا.',
                    'en' => 'The gap between graduate and professional is not algorithms — it is these practical skills everyone learns too late. Learn them early.',
                ],
                'body' => ['ar' => $this->a4ar(), 'en' => $this->a4en()],
            ],
        ];
    }

    private function a1ar(): string
    {
        return <<<'HTML'
<p>أكثر سؤال يصلني من طلاب الجامعات: «أريد تعلّم البرمجة لكنّي لا أعرف من أين أبدأ، وأشعر أنّني أضيع وقتي بين مئة دورة». المشكلة ليست في قلّة المصادر، بل في غياب <strong>خارطة طريق واضحة</strong>. هذا المقال يعطيك ترتيبًا عمليًّا تتبعه دون تشتّت.</p>
<h2>القاعدة الذهبية: اختر مسارًا واحدًا أوّلًا</h2>
<p>أكبر خطأ هو محاولة تعلّم كلّ شيء دفعةً واحدة. اختر مسارًا واحدًا حسب ما يثير اهتمامك:</p>
<ul>
<li><strong>تطوير الويب</strong>: مرئيّ ومجزٍ بسرعة، وسوق عمل واسع.</li>
<li><strong>تطبيقات الجوال</strong>: إن كنت تحبّ بناء أشياء يستخدمها الناس على هواتفهم.</li>
<li><strong>علم البيانات والذكاء الاصطناعي</strong>: إن كنت تميل للرياضيات والتحليل.</li>
</ul>
<p>لا تقلق من «الاختيار الخاطئ» — الأساسيات مشتركة، وستنتقل بسهولة لاحقًا.</p>
<h2>المرحلة 1: أتقن لغة واحدة (٤–٦ أسابيع)</h2>
<p>اختر لغة واحدة والتزم بها: <strong>Python</strong> للمبتدئين وعلم البيانات، أو <strong>JavaScript</strong> للويب. لا تقفز بين اللغات. ركّز على: المتغيّرات، الشروط، الحلقات، الدوال، المصفوفات والكائنات. اكتب كودًا كلّ يوم ولو ٣٠ دقيقة — الاستمرارية أهمّ من المدّة.</p>
<h2>المرحلة 2: هياكل البيانات والخوارزميات — بقدر</h2>
<p>تحتاج فهمًا <em>عمليًّا</em> لا أكاديميًّا مفرطًا: المصفوفات، القوائم، الخرائط (Hash Maps)، والبحث والترتيب الأساسي. حلّ ٢–٣ مسائل أسبوعيًّا على منصّة مثل LeetCode بمستوى سهل. لا تغرق هنا لشهور؛ الهدف بناء حدس برمجي لا حفظ الحلول.</p>
<h2>المرحلة 3: ابنِ مشروعًا حقيقيًّا (الأهمّ)</h2>
<p>هنا يحدث التعلّم الحقيقي. لا تنتظر حتى «تجهز». ابنِ مشروعًا صغيرًا يحلّ مشكلة تخصّك:</p>
<ul>
<li>تطبيق لتنظيم جدول محاضراتك.</li>
<li>موقع يعرض مشاريعك (Portfolio).</li>
<li>أداة تحسب معدّلك التراكمي.</li>
</ul>
<p>ستواجه أخطاءً وتبحث عن حلول — وهذا بالضبط ما يفعله المبرمج المحترف يوميًّا.</p>
<h2>المرحلة 4: Git ومعرض الأعمال</h2>
<p>تعلّم <strong>Git وGitHub</strong> مبكّرًا وارفع كلّ مشاريعك. حساب GitHub منظّم أقوى من شهادة لكثير من أصحاب العمل. اكتب ملفّ <code>README</code> لكلّ مشروع يشرح فكرته وكيفية تشغيله.</p>
<h2>أخطاء شائعة تجنّبها</h2>
<ul>
<li><strong>إدمان الدورات</strong>: مشاهدة دون تطبيق وهمٌ بالتقدّم. القاعدة: ساعة تعلّم مقابل ساعتي تطبيق.</li>
<li><strong>القفز بين اللغات</strong>: يشتّتك ويُبطئك.</li>
<li><strong>الخوف من الأخطاء</strong>: الخطأ هو طريق التعلّم، لا عدوّه.</li>
</ul>
<h2>الخلاصة</h2>
<p>البرمجة مهارة تُبنى بالممارسة لا بالمشاهدة. اختر مسارًا، أتقن لغة، ابنِ مشاريع، وشارك عملك. خلال ٣–٦ أشهر من العمل المنتظم ستملك أساسًا حقيقيًّا يميّزك عن أقرانك. ابدأ اليوم — أوّل سطر كود هو الأصعب، وكلّ ما بعده أسهل.</p>
HTML;
    }

    private function a1en(): string
    {
        return <<<'HTML'
<p>The most common question I get from students: "I want to learn to code but I don't know where to start." The problem is rarely a lack of resources — it is the absence of a clear roadmap. This article gives you a practical order to follow without getting lost.</p>
<h2>The golden rule: pick one track first</h2>
<p>The biggest mistake is trying to learn everything at once. Pick one: <strong>web development</strong> (visual, fast feedback, huge job market), <strong>mobile apps</strong>, or <strong>data science & AI</strong> if you enjoy math. The fundamentals are shared, so you can switch later.</p>
<h2>Stage 1: master one language (4–6 weeks)</h2>
<p>Choose <strong>Python</strong> or <strong>JavaScript</strong> and stick with it. Focus on variables, conditionals, loops, functions, arrays and objects. Code every day, even 30 minutes — consistency beats duration.</p>
<h2>Stage 2: data structures & algorithms — in moderation</h2>
<p>You need a practical grasp: arrays, lists, hash maps, basic search and sort. Solve 2–3 easy problems weekly. Don't drown here for months; the goal is intuition, not memorizing solutions.</p>
<h2>Stage 3: build a real project (the most important)</h2>
<p>Real learning happens here. Build something small that solves your own problem: a class-schedule app, a portfolio site, a GPA calculator. You will hit errors and search for fixes — exactly what professionals do daily.</p>
<h2>Stage 4: Git & a portfolio</h2>
<p>Learn <strong>Git and GitHub</strong> early and push every project. An organized GitHub profile beats a certificate for many employers. Write a <code>README</code> for each project.</p>
<h2>Common mistakes</h2>
<ul>
<li><strong>Course addiction</strong>: watching without building is fake progress. One hour learning, two hours doing.</li>
<li><strong>Language hopping</strong>: it slows you down.</li>
<li><strong>Fear of errors</strong>: errors are the path to learning, not the enemy.</li>
</ul>
<h2>Conclusion</h2>
<p>Coding is built by practice, not by watching. Pick a track, master a language, build projects, share your work. In 3–6 months of steady effort you will have a real foundation. Start today — the first line is the hardest.</p>
HTML;
    }

    private function a2ar(): string
    {
        return <<<'HTML'
<p>الذكاء الاصطناعي ليس غشًّا، وليس سحرًا. هو <strong>أداة قويّة</strong> تضاعف إنتاجيّتك إن أحسنت استخدامها، وتُضعف مهاراتك إن جعلتها تفكّر بدلًا عنك. الفرق بين الطالب الذي يستفيد والطالب الذي يتضرّر هو في <em>الطريقة</em>.</p>
<h2>المبدأ الأساسي: مساعد لا بديل</h2>
<p>تعامل مع أدوات مثل ChatGPT وClaude وGitHub Copilot كأنّها <strong>زميل ذكيّ متاح ٢٤ ساعة</strong>: تسأله، يشرح لك، يقترح، يراجع — لكنّك أنت من يفهم ويقرّر. القاعدة الحاكمة: <strong>لا تسلّم شيئًا لا تفهمه.</strong></p>
<h2>استخدامات تُسرّع دراستك (دون أن تُفقدك المهارة)</h2>
<ul>
<li><strong>الفهم</strong>: «اشرح لي مفهوم الـRecursion كأنّني مبتدئ، بمثال بسيط». الذكاء الاصطناعي مدرّس خاصّ لا يملّ من إعادة الشرح.</li>
<li><strong>التلخيص</strong>: ألصق فصلًا طويلًا واطلب نقاطًا رئيسية، ثمّ تحقّق منها بنفسك.</li>
<li><strong>مراجعة الكود</strong>: «راجع هذا الكود وأخبرني بالأخطاء وكيف أحسّنه» — ستتعلّم من ملاحظاته.</li>
<li><strong>توليد أمثلة وأسئلة</strong>: اطلب منه أسئلة تدريبية على موضوع لاختبار فهمك.</li>
</ul>
<h2>فنّ السؤال (Prompting)</h2>
<p>جودة الإجابة من جودة السؤال. اتبع هذه القاعدة: <strong>السياق + المطلوب + القيود</strong>. مثال سيّئ: «اكتب لي كود فرز». مثال جيّد: «أنا طالب أتعلّم Python. اكتب دالّة فرز فقاعي مع شرح كلّ سطر بتعليق عربيّ، ودون استخدام مكتبات جاهزة». كلّما حدّدت أكثر، كانت النتيجة أدقّ وأنفع.</p>
<h2>الفخّ الذي يقع فيه الكثيرون</h2>
<blockquote>إن نسخت حلًّا لا تفهمه وسلّمته، فأنت لم تتعلّم شيئًا — بل خدعت نفسك قبل أستاذك.</blockquote>
<p>الاعتماد الكامل يُضعف عضلتك العقلية تمامًا كما يُضعف الاعتماد الدائم على الآلة الحاسبة قدرتك على الحساب الذهني. استخدمه ليرفعك، لا ليحملك.</p>
<h2>النزاهة الأكاديمية</h2>
<p>اعرف سياسة جامعتك. الأصل أن تستخدم الذكاء الاصطناعي للفهم والمراجعة، لا لكتابة واجبك كاملًا وتسليمه باسمك. كن شفّافًا، واجعل العمل النهائي عملك أنت بعد أن فهمته وأعدت صياغته.</p>
<h2>الخلاصة</h2>
<p>الذكاء الاصطناعي يضاعف قدرات من يملك أساسًا، ولا يصنع أساسًا لمن لا يملكه. اجعله مدرّسك ومراجعك ومساعدك — لكن ابقَ أنت العقل المفكّر. الطلاب الذين يتقنون هذا التوازن اليوم هم من سيتصدّرون سوق العمل غدًا.</p>
HTML;
    }

    private function a2en(): string
    {
        return <<<'HTML'
<p>AI is not cheating, and it is not magic. It is a powerful tool that multiplies your output when used well and erodes your skills when you let it think for you. The difference is in the method.</p>
<h2>The core principle: assistant, not replacement</h2>
<p>Treat ChatGPT, Claude and GitHub Copilot like a smart colleague available 24/7: ask, learn, get suggestions and reviews — but you understand and decide. The rule: <strong>never submit something you don't understand.</strong></p>
<h2>Uses that speed up your studies</h2>
<ul>
<li><strong>Understanding</strong>: "Explain recursion to me as a beginner with a simple example." It is a tutor that never tires of re-explaining.</li>
<li><strong>Summarizing</strong>: paste a long chapter, ask for key points, then verify them yourself.</li>
<li><strong>Code review</strong>: "Review this code, list the bugs and how to improve it." You learn from its notes.</li>
<li><strong>Practice questions</strong>: ask it to quiz you on a topic.</li>
</ul>
<h2>The art of prompting</h2>
<p>Answer quality follows question quality: <strong>context + task + constraints</strong>. Bad: "write sorting code." Good: "I'm a student learning Python. Write a bubble sort with each line commented, no libraries."</p>
<h2>The trap many fall into</h2>
<blockquote>If you copy a solution you don't understand and submit it, you haven't learned anything — you fooled yourself before your professor.</blockquote>
<p>Total dependence weakens your mental muscle, like always relying on a calculator weakens mental arithmetic. Use it to lift you, not to carry you.</p>
<h2>Academic integrity</h2>
<p>Know your university's policy. Use AI to understand and review, not to write your whole assignment. Be transparent, and make the final work your own.</p>
<h2>Conclusion</h2>
<p>AI multiplies the abilities of those who have a foundation; it does not build one for those who don't. Make it your tutor and reviewer — but stay the thinking mind.</p>
HTML;
    }

    private function a3ar(): string
    {
        return <<<'HTML'
<p>الحقيقة الصادمة: <strong>معظم مشاريع التخرّج لا تفشل بسبب صعوبة الفكرة، بل بسبب سوء الإدارة</strong> — تأجيل، نطاق غير واضح، وعمل في الليلة الأخيرة. هذا الدليل يرتّب لك الطريق لمشروع تفخر بعرضه.</p>
<h2>1. اختيار الفكرة: حلّ مشكلة حقيقية</h2>
<p>أفضل المشاريع تحلّ مشكلة تفهمها. ابحث في محيطك: مشكلة في جامعتك، في عملك الجزئي، في هوايتك. الفكرة الجيّدة <strong>محدّدة وقابلة للتنفيذ في الوقت المتاح</strong> — لا «منصّة تنافس فيسبوك».</p>
<h2>2. حدّد النطاق (Scope) بصرامة</h2>
<p>اكتب قائمتين: «ميزات أساسية لا بدّ منها» و«ميزات إضافية إن توفّر وقت». مشروع تخرّج ناجح يُنجز الأساسيات بإتقان، لا عشر ميزات نصف مكتملة. <strong>النطاق الزاحف (Scope Creep) هو القاتل الأوّل.</strong></p>
<h2>3. وثيقة متطلّبات مختصرة</h2>
<p>قبل أيّ كود، اكتب صفحة واحدة: ما المشكلة؟ من المستخدم؟ ما الميزات؟ ما التقنيات؟ هذه الوثيقة بوصلتك وتُبهر لجنة المناقشة لاحقًا.</p>
<h2>4. اختيار التقنيات: المألوف أأمن</h2>
<p>لا تتعلّم تقنية جديدة كليًّا في مشروع التخرّج. استخدم ما تتقنه أو ما يمكنك إتقانه بسرعة. التقنية الأنسب هي التي تُنجز المشروع في الوقت، لا الأحدث.</p>
<h2>5. خطّط بالأسابيع (منهج رشيق مبسّط)</h2>
<ul>
<li><strong>أسابيع 1–2</strong>: البحث، الوثيقة، تصميم قاعدة البيانات والواجهات (Wireframes).</li>
<li><strong>أسابيع 3–8</strong>: التنفيذ على دفعات أسبوعية — كلّ أسبوع ميزة كاملة قابلة للتشغيل.</li>
<li><strong>أسابيع 9–10</strong>: الاختبار، إصلاح الأخطاء، التوثيق، تجهيز العرض.</li>
</ul>
<p>القاعدة: <strong>دائمًا اجعل المشروع قابلًا للتشغيل</strong>، ولو بميزات قليلة. لا تبنِ كلّ شيء ثمّ تجرّب في النهاية.</p>
<h2>6. استخدم Git من اليوم الأوّل</h2>
<p>احفظ تقدّمك على GitHub بانتظام. هذا يحميك من فقدان العمل، ويُظهر للجنة (ولأصحاب العمل) رحلة بنائك خطوة بخطوة.</p>
<h2>7. التوثيق والعرض: نصف الدرجة هنا</h2>
<p>مشروع رائع بعرض ضعيف يأخذ درجة متوسّطة. جهّز: توثيقًا واضحًا، عرضًا تقديميًّا يحكي «المشكلة ← الحلّ ← النتيجة»، وعرضًا حيًّا (Demo) جرّبته عشرات المرّات حتى لا يتعطّل أمام اللجنة.</p>
<h2>أخطاء قاتلة</h2>
<ul>
<li>التأجيل والاعتماد على «الليلة الأخيرة».</li>
<li>طموح زائد ونطاق ضخم.</li>
<li>إهمال التوثيق والعرض.</li>
</ul>
<h2>الخلاصة</h2>
<p>مشروع التخرّج الاحترافي = فكرة محدّدة + نطاق منضبط + عمل أسبوعيّ منتظم + Git + عرض مُتقَن. اتبع هذا الترتيب وسيكون مشروعك من الأفضل في دفعتك — وأوّل سطر في سيرتك الذاتية.</p>
HTML;
    }

    private function a3en(): string
    {
        return <<<'HTML'
<p>The shocking truth: most graduation projects fail not because the idea is hard, but because of poor management — procrastination, unclear scope, and last-night work. This guide orders the path to a project you are proud to present.</p>
<h2>1. Pick an idea: solve a real problem</h2>
<p>The best projects solve a problem you understand. Look around you. A good idea is <strong>specific and achievable in the time you have</strong> — not "a platform to rival Facebook."</p>
<h2>2. Define the scope strictly</h2>
<p>Write two lists: "must-have core features" and "nice-to-have if time allows." A successful project nails the basics, not ten half-finished features. <strong>Scope creep is the number-one killer.</strong></p>
<h2>3. A short requirements document</h2>
<p>Before any code, write one page: what's the problem, who's the user, what features, what tech. It is your compass and impresses the committee later.</p>
<h2>4. Choose familiar tech</h2>
<p>Don't learn an entirely new stack for your final project. Use what you know. The best tech is the one that ships on time, not the newest.</p>
<h2>5. Plan in weeks (lightweight Agile)</h2>
<ul>
<li><strong>Weeks 1–2</strong>: research, document, database & wireframes.</li>
<li><strong>Weeks 3–8</strong>: build in weekly increments — one working feature per week.</li>
<li><strong>Weeks 9–10</strong>: testing, bug fixing, documentation, presentation.</li>
</ul>
<p>Rule: <strong>always keep the project runnable</strong>, even with few features.</p>
<h2>6. Use Git from day one</h2>
<p>Commit to GitHub regularly. It protects your work and shows your build journey to the committee and employers.</p>
<h2>7. Documentation & presentation: half the grade</h2>
<p>A great project with a weak presentation gets an average grade. Prepare clear docs, a "problem → solution → result" deck, and a demo you have rehearsed dozens of times.</p>
<h2>Fatal mistakes</h2>
<ul>
<li>Procrastination and the "last night" approach.</li>
<li>Over-ambition and huge scope.</li>
<li>Neglecting documentation and the presentation.</li>
</ul>
<h2>Conclusion</h2>
<p>A professional graduation project = a specific idea + disciplined scope + steady weekly work + Git + a polished presentation. Follow this order and your project will be among the best in your class.</p>
HTML;
    }

    private function a4ar(): string
    {
        return <<<'HTML'
<p>تتخرّج وأنت تعرف الخوارزميات وقواعد البيانات نظريًّا، ثمّ تصطدم بسوق العمل ليكتشف أنّ المطلوب مهارات أخرى لم تُدرّسها الجامعة. إليك ٧ مهارات تصنع الفرق — تعلّمها مبكّرًا لتسبق أقرانك.</p>
<h2>1. التحكّم بالإصدارات (Git)</h2>
<p>لا توجد شركة جادّة لا تستخدم Git. تعلّم: <code>commit</code>, <code>branch</code>, <code>merge</code>, <code>pull request</code>. ليست أداة فحسب، بل لغة التعاون بين المبرمجين.</p>
<h2>2. الكود النظيف (Clean Code)</h2>
<p>الكود يُقرأ أضعاف ما يُكتب. أسماء واضحة، دوال صغيرة تفعل شيئًا واحدًا، وتجنّب التكرار. الكود الذي يفهمه زميلك بعد ٦ أشهر أثمن من الكود «الذكيّ» الغامض.</p>
<h2>3. كتابة الاختبارات (Testing)</h2>
<p>الجامعة تعلّمك أن تكتب كودًا «يعمل»؛ السوق يريد كودًا «يبقى يعمل». تعلّم اختبارات الوحدة (Unit Tests) لتثق بتعديلاتك دون خوف من كسر ما بنيته.</p>
<h2>4. تصحيح الأخطاء بمنهجية (Debugging)</h2>
<p>المحترف لا يخمّن — بل يستخدم نقاط التوقّف (Breakpoints) ويقرأ رسائل الخطأ بعناية ويعزل المشكلة خطوةً خطوة. مهارة التصحيح المنهجي تختصر ساعات من المعاناة.</p>
<h2>5. قواعد البيانات والاستعلامات الواقعية</h2>
<p>تتعلّم SQL نظريًّا، لكنّ العمل يتطلّب تصميم جداول فعّالة، فهرسة (Indexing) للأداء، وكتابة استعلامات لا تُبطئ التطبيق. تدرّب على بيانات حقيقية كبيرة.</p>
<h2>6. النشر والتشغيل (Deployment)</h2>
<p>«يعمل على جهازي» ليست كافية. تعلّم رفع مشروعك على خادم أو خدمة سحابية، وفهم أساسيات البيئات والمتغيّرات والنطاقات وSSL. مشروع منشور يعمل أقوى من عشرة مشاريع على جهازك.</p>
<h2>7. الأمان الأساسي</h2>
<p>افهم الأخطاء الشائعة: حقن SQL، XSS، تخزين كلمات المرور بشكل آمن (Hashing لا تشفير عكسي)، والتحقّق من مدخلات المستخدم. ثغرة واحدة قد تكلّف شركة سمعتها.</p>
<h2>مهارة فوق المهارات: قراءة كود الآخرين</h2>
<p>ستقضي وقتًا في فهم كود كتبه غيرك أكثر ممّا تكتب من الصفر. تدرّب على قراءة مشاريع مفتوحة المصدر على GitHub — إنّها أعظم مكتبة تعليمية مجّانية في العالم.</p>
<h2>الخلاصة</h2>
<p>الفجوة بين الخرّيج والمحترف ليست في الذكاء، بل في هذه المهارات العملية. كلّ مهارة منها تتعلّمها أثناء دراستك تجعلك أقرب لوظيفة أحلامك. لا تنتظر التخرّج — ابدأ الآن بواحدة، وأتقنها على مشروع حقيقي.</p>
HTML;
    }

    private function a4en(): string
    {
        return <<<'HTML'
<p>You graduate knowing algorithms and databases in theory, then hit a job market that wants other skills university never taught. Here are 7 that make the difference — learn them early to get ahead.</p>
<h2>1. Version control (Git)</h2>
<p>No serious company skips Git. Learn <code>commit</code>, <code>branch</code>, <code>merge</code>, <code>pull request</code>. It is the language of collaboration between developers.</p>
<h2>2. Clean code</h2>
<p>Code is read far more than it is written. Clear names, small single-purpose functions, no duplication. Code your teammate understands in six months beats clever, cryptic code.</p>
<h2>3. Writing tests</h2>
<p>University teaches you to write code that "works"; the market wants code that "keeps working." Learn unit tests so you can change code without fear of breaking it.</p>
<h2>4. Methodical debugging</h2>
<p>Professionals don't guess — they use breakpoints, read error messages carefully, and isolate the problem step by step. This skill saves hours of pain.</p>
<h2>5. Real-world databases & queries</h2>
<p>You learn SQL in theory, but work requires effective table design, indexing for performance, and queries that don't slow the app. Practice on large, real data.</p>
<h2>6. Deployment</h2>
<p>"It works on my machine" isn't enough. Learn to deploy to a server or cloud, and understand environments, variables, domains and SSL. One deployed project beats ten on your laptop.</p>
<h2>7. Basic security</h2>
<p>Understand common flaws: SQL injection, XSS, secure password storage (hashing, not reversible encryption), and validating user input. One vulnerability can cost a company its reputation.</p>
<h2>The meta-skill: reading others' code</h2>
<p>You will spend more time understanding code others wrote than writing from scratch. Practice by reading open-source projects on GitHub — the greatest free learning library on earth.</p>
<h2>Conclusion</h2>
<p>The gap between graduate and professional isn't intelligence — it's these practical skills. Each one you learn while studying brings you closer to your dream job. Don't wait to graduate — start now with one, on a real project.</p>
HTML;
    }
}
