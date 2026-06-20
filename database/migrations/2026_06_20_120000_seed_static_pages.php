<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Seed the standard footer/legal CMS pages (admin-editable afterwards).
 * Uses firstOrCreate by slug so it's safe to re-run and never overwrites edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->pages() as $slug => $p) {
            Page::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $p['title'],
                    'body' => $p['body'],
                    'meta_title' => $p['title'],
                    'meta_description' => $p['meta'] ?? ['ar' => '', 'en' => ''],
                    'is_published' => true,
                ],
            );
        }
    }

    public function down(): void
    {
        Page::whereIn('slug', array_keys($this->pages()))->delete();
    }

    private function pages(): array
    {
        return [
            'about' => [
                'title' => ['ar' => 'عن فنون', 'en' => 'About Fnoon'],
                'meta' => ['ar' => 'تعرّف على منصّة فنون لتنزيل البرامج والتطبيقات والقوالب.', 'en' => 'Learn about Fnoon — your platform for software, apps and templates.'],
                'body' => [
                    'ar' => '<h2>من نحن</h2><p>فنون منصّة عربية متخصّصة في توفير أحدث <strong>البرامج والتطبيقات والقوالب والإضافات والأكواد</strong> بطريقة موثوقة وآمنة وسريعة. نحرص على تقديم كل محتوى بوصفٍ وافٍ وروابط مباشرة وإصدارات محدَّثة باستمرار.</p><h2>ماذا نقدّم</h2><ul><li>مكتبة متجدّدة من البرامج والأدوات لمختلف الأنظمة.</li><li>قوالب وتصاميم وإضافات جاهزة للاستخدام.</li><li>محتوى تعليمي ومختبرات تفاعلية للتعلّم والبناء.</li><li>تجربة تحميل سهلة وآمنة مع فحص للملفّات.</li></ul><h2>رؤيتنا</h2><p>أن نكون الوجهة الأولى للمستخدم العربي للوصول إلى الأدوات الرقمية التي يحتاجها، بثقةٍ وجودة.</p>',
                    'en' => '<h2>Who we are</h2><p>Fnoon is a platform that delivers the latest <strong>software, apps, templates, plugins and code</strong> in a reliable, safe and fast way. Every item comes with a clear description, direct links and continuously updated versions.</p><h2>What we offer</h2><ul><li>A growing library of software and tools for every platform.</li><li>Ready-to-use templates, designs and plugins.</li><li>Educational content and interactive labs to learn and build.</li><li>An easy, safe download experience with file scanning.</li></ul><h2>Our vision</h2><p>To be the first destination for accessing the digital tools you need — with trust and quality.</p>',
                ],
            ],
            'mission' => [
                'title' => ['ar' => 'رسالتنا', 'en' => 'Our Mission'],
                'meta' => ['ar' => 'رسالة فنون وقيمها.', 'en' => 'Fnoon’s mission and values.'],
                'body' => [
                    'ar' => '<h2>رسالتنا</h2><p>تمكين المستخدم العربي من الوصول إلى أحدث البرامج والأدوات الرقمية بسهولة وأمان، ودعم رحلته في التعلّم والإبداع.</p><h2>قيمنا</h2><ul><li><strong>الجودة:</strong> محتوى منتقى ومحدَّث.</li><li><strong>الأمان:</strong> فحص الملفّات وحماية بيانات المستخدم.</li><li><strong>الشفافية:</strong> وضوح في الوصف والمصادر.</li><li><strong>خدمة المجتمع:</strong> محتوى تعليمي ودعم مستمر.</li></ul>',
                    'en' => '<h2>Our mission</h2><p>To empower users to reach the latest software and digital tools easily and safely, and to support their journey of learning and creating.</p><h2>Our values</h2><ul><li><strong>Quality:</strong> curated, up-to-date content.</li><li><strong>Safety:</strong> file scanning and user-data protection.</li><li><strong>Transparency:</strong> clear descriptions and sources.</li><li><strong>Community:</strong> educational content and ongoing support.</li></ul>',
                ],
            ],
            'privacy' => [
                'title' => ['ar' => 'سياسة الخصوصية', 'en' => 'Privacy Policy'],
                'meta' => ['ar' => 'كيف نجمع بياناتك ونستخدمها ونحميها.', 'en' => 'How we collect, use and protect your data.'],
                'body' => [
                    'ar' => '<p>نحترم خصوصيتك ونلتزم بحماية بياناتك. توضّح هذه السياسة البيانات التي نجمعها وكيفية استخدامها.</p><h2>البيانات التي نجمعها</h2><ul><li>بيانات الحساب (الاسم والبريد) عند التسجيل.</li><li>بيانات تقنية (عنوان IP، نوع المتصفّح، الصفحات المُزارة) لأغراض الإحصاء والأمان.</li><li>ملفات تعريف الارتباط (Cookies) لتحسين التجربة.</li></ul><h2>كيف نستخدم البيانات</h2><ul><li>لتشغيل الموقع وتحسين الخدمات.</li><li>للتواصل معك وإرسال التحديثات إن اشتركت.</li><li>للحماية من إساءة الاستخدام والاحتيال.</li></ul><h2>المشاركة</h2><p>لا نبيع بياناتك. قد نشاركها فقط مع مزوّدي خدمات موثوقين لتشغيل الموقع، أو عند الالتزام القانوني.</p><h2>حقوقك</h2><p>يمكنك الوصول إلى بياناتك أو تعديلها أو طلب حذفها بالتواصل معنا.</p><h2>التواصل</h2><p>لأي استفسار حول الخصوصية، تواصل معنا عبر صفحة <a href="/contact">تواصل معنا</a>.</p>',
                    'en' => '<p>We respect your privacy and are committed to protecting your data. This policy explains what we collect and how we use it.</p><h2>Data we collect</h2><ul><li>Account data (name and email) when you register.</li><li>Technical data (IP address, browser, visited pages) for analytics and security.</li><li>Cookies to improve your experience.</li></ul><h2>How we use it</h2><ul><li>To operate the site and improve our services.</li><li>To contact you and send updates if you subscribe.</li><li>To protect against abuse and fraud.</li></ul><h2>Sharing</h2><p>We do not sell your data. We may share it only with trusted service providers to run the site, or when legally required.</p><h2>Your rights</h2><p>You can access, correct or request deletion of your data by contacting us.</p><h2>Contact</h2><p>For any privacy question, reach us via the <a href="/contact">Contact us</a> page.</p>',
                ],
            ],
            'terms' => [
                'title' => ['ar' => 'شروط الاستخدام', 'en' => 'Terms of Use'],
                'meta' => ['ar' => 'شروط وأحكام استخدام موقع فنون.', 'en' => 'Terms and conditions for using Fnoon.'],
                'body' => [
                    'ar' => '<p>باستخدامك للموقع فإنك توافق على هذه الشروط.</p><h2>استخدام الموقع</h2><p>تلتزم باستخدام الموقع للأغراض المشروعة فقط، وعدم الإضرار به أو بمستخدميه.</p><h2>الملكية الفكرية</h2><p>المحتوى والعلامات التجارية مملوكة لأصحابها. إن كنت تملك حقوقًا على محتوى منشور وتريد إزالته، راجع صفحة <a href="/dmca">حقوق النشر (DMCA)</a>.</p><h2>إخلاء المسؤولية</h2><p>يُقدَّم المحتوى «كما هو». لا نتحمّل مسؤولية أي ضرر ناتج عن استخدام الملفّات؛ يرجى فحصها والتأكّد منها.</p><h2>التعديلات</h2><p>قد نحدّث هذه الشروط من وقت لآخر، ويسري التحديث فور نشره.</p>',
                    'en' => '<p>By using the site you agree to these terms.</p><h2>Using the site</h2><p>You agree to use the site only for lawful purposes and not to harm it or its users.</p><h2>Intellectual property</h2><p>Content and trademarks belong to their owners. If you own rights to published content and want it removed, see the <a href="/dmca">DMCA</a> page.</p><h2>Disclaimer</h2><p>Content is provided “as is”. We are not liable for damage from using files; please scan and verify them.</p><h2>Changes</h2><p>We may update these terms from time to time; changes take effect once published.</p>',
                ],
            ],
            'advertise' => [
                'title' => ['ar' => 'أعلن معنا', 'en' => 'Advertise with us'],
                'meta' => ['ar' => 'روّج لعلامتك أمام جمهور مهتمّ بالبرامج والتقنية.', 'en' => 'Promote your brand to a tech-savvy audience.'],
                'body' => [
                    'ar' => '<h2>لماذا تعلن في فنون؟</h2><p>يصل موقعنا إلى جمهور واسع مهتمّ بالبرامج والتصميم والتقنية، ما يجعله مكانًا مثاليًّا للترويج لمنتجاتك وخدماتك.</p><h2>المساحات الإعلانية</h2><ul><li>بانرات في الصفحة الرئيسية وصفحات البرامج.</li><li>إعلانات ضمن المحتوى وصفحة التحميل.</li><li>رعاية محتوى أو حملات مخصّصة.</li></ul><h2>ابدأ الآن</h2><p>للحصول على الباقات والأسعار، تواصل معنا عبر صفحة <a href="/contact">تواصل معنا</a> وسنردّ عليك بأقرب وقت.</p>',
                    'en' => '<h2>Why advertise on Fnoon?</h2><p>We reach a broad audience interested in software, design and technology — an ideal place to promote your products and services.</p><h2>Ad placements</h2><ul><li>Banners on the homepage and product pages.</li><li>In-content and download-page ads.</li><li>Sponsored content or custom campaigns.</li></ul><h2>Get started</h2><p>For packages and pricing, reach us via the <a href="/contact">Contact us</a> page and we’ll get back to you shortly.</p>',
                ],
            ],
            'donate' => [
                'title' => ['ar' => 'ادعمنا', 'en' => 'Support us'],
                'meta' => ['ar' => 'ساهم في استمرار فنون وتطويره.', 'en' => 'Help keep Fnoon running and growing.'],
                'body' => [
                    'ar' => '<h2>لماذا ندعو لدعمنا؟</h2><p>نعمل على إبقاء المحتوى مجانيًّا ومتاحًا للجميع. دعمك يساعدنا على تغطية تكاليف الخوادم والتطوير وتقديم محتوى أفضل باستمرار.</p><h2>كيف يساعد دعمك</h2><ul><li>خوادم أسرع وسعة تخزين أكبر.</li><li>تحديثات وميزات جديدة.</li><li>محتوى تعليمي مجاني.</li></ul><h2>طرق الدعم</h2><p>سنوفّر قريبًا روابط الدعم المباشر. حتى ذلك الحين، يمكنك دعمنا بمشاركة الموقع مع أصدقائك، أو التواصل معنا عبر صفحة <a href="/contact">تواصل معنا</a>.</p>',
                    'en' => '<h2>Why support us?</h2><p>We work to keep our content free and available to everyone. Your support helps cover server and development costs and deliver better content.</p><h2>How it helps</h2><ul><li>Faster servers and more storage.</li><li>New updates and features.</li><li>Free educational content.</li></ul><h2>Ways to support</h2><p>Direct donation links are coming soon. Meanwhile, you can support us by sharing the site with friends, or reach us via the <a href="/contact">Contact us</a> page.</p>',
                ],
            ],
        ];
    }
};
