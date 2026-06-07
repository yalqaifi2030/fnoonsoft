# Prompt: موقع تحميل البرامج والتطبيقات (Laravel + Filament)

> انسخ هذا الـ Prompt كاملًا وألصقه في أداة الذكاء الاصطناعي التي تبني بها المشروع (Claude / Cursor / غيرها). معدّ ليكون مرجعًا متكاملًا للرؤية، التقنيات، الميزات، التصميم، وهيكل قاعدة البيانات.

---

## 0. الدور والسياق

أنت مهندس برمجيات Full-Stack خبير في **Laravel 11** و **Filament 3.3**. مهمتك بناء **منصّة عالمية** لتحميل البرامج والتطبيقات والسكربتات البرمجية وقوالب المواقع والإضافات (شبيهة بـ Softpedia / FileHippo / Uptodown / Envato من حيث الفكرة، لكن بهوية عصرية وأداء عالٍ).

**ثلاثة متطلّبات حاكمة لا تتنازل عنها:**
1. **منصّة عالمية:** الإنجليزية هي اللغة الافتراضية + دعم العربية (RTL)، وبنية تحتية للترجمة قابلة للتوسّع لأي لغة.
2. **محرّك رفع قوي جدًا:** يتحمّل ملفًا واحدًا حتى **30 جيجابايت**، سريع، قابل للاستئناف عند انقطاع الاتصال (Resumable).
3. **توصيل عالمي سريع:** التخزين على **Cloudflare R2** (صفر رسوم egress) مع CDN ليُحمّل الزائر من أقرب نقطة جغرافية.

التزم بالمبادئ التالية في كل خطوة:
- كود نظيف، منظّم، يتبع معايير **PSR-12** و **Laravel conventions**.
- فصل واضح بين طبقة العرض (Blade) وطبقة المنطق (Services / Actions).
- لا تستخدم build step للأصول العامة (بدون Vite / Webpack) — كل شيء عبر CDN.
- أمان أولًا: تنظيف المدخلات، Rate limiting، فحص الملفات المرفوعة (asynchronous للملفات الكبيرة).
- دعم كامل للإنجليزية (LTR) والعربية (RTL) ببنية ترجمة جاهزة (i18n).

---

## 1. الرؤية والهدف

منصّة **عالمية** تتيح للزوار حول العالم تصفّح وتحميل البرامج والتطبيقات والسكربتات والقوالب والإضافات بسهولة وسرعة، مع لوحة إدارة قوية لإدارة المحتوى والإحصائيات، ولوحة رفع متخصّصة تتحمّل الملفات العملاقة. الجمهور عالمي لذا **الإنجليزية هي الافتراضية (LTR)** مع دعم كامل للعربية (RTL)، وتصميم نظيف سريع متجاوب.

**القيم المحورية:**
- سرعة التصفح والتحميل عالميًا (CDN + edge delivery).
- رفع موثوق للملفات الضخمة حتى 30GB مع الاستئناف.
- ثقة الزائر (تحقّق، إصدارات موثّقة، فحص خلوّ من البرمجيات الخبيثة، checksum).
- محتوى منظّم متعدّد الأنواع وقابل للبحث.
- تجربة موبايل ممتازة + i18n.

---

## 2. التقنيات المستخدمة

### الـ Backend
| التقنية | الإصدار | الدور |
|---------|---------|------|
| **PHP** | 8.2+ | لغة الـ backend |
| **Laravel** | 11.x | الإطار الرئيسي |
| **Filament** | 3.3 | لوحة الإدارة |
| **Livewire** | (مدمج مع Filament) | تفاعلية بدون JS كثير |
| **Laravel Sanctum** | 4.x | API authentication |
| **Spatie Permission** | 6.25 | إدارة الأدوار والصلاحيات |
| **Spatie MediaLibrary** | 11.22 | الصور/اللقطات/الأيقونات فقط (**ليست** لملفات التحميل الكبيرة) |
| **league/flysystem-aws-s3-v3** | 3.x | تشغيل Cloudflare R2 عبر S3 API (disk باسم `r2`) |
| **tus / Uppy (resumable)** | — | محرّك الرفع المقطّع القابل للاستئناف حتى 30GB |
| **Laravel Queue (Redis) + Horizon** | — | فحص الملفات + توليد checksum + المهام الخلفية |
| **ClamAV / VirusTotal API** | — | فحص البرمجيات الخبيثة بعد اكتمال الرفع (async) |

> ⚠️ **قاعدة حاسمة:** ملفات التحميل (البرامج/السكربتات/القوالب) **لا تُرفع عبر Spatie MediaLibrary ولا عبر سيرفر PHP**. تُرفع مباشرة إلى Cloudflare R2 عبر **Presigned URLs** بأسلوب مقطّع قابل للاستئناف. MediaLibrary تبقى للصور واللقطات والأيقونات فقط (ملفات صغيرة).

### الـ Frontend (موقع عام)
- **Tailwind CSS** عبر CDN (تخصيص في `app.blade.php`)
- **Alpine.js** للتفاعلية الخفيفة
- **Google Fonts:** Tajawal، Cairo، Playfair Display
- **Font Awesome 6.5**
- **بدون Vite/Webpack** — المشروع لا يستخدم build step للأصول

### الـ Frontend (لوحة الإدارة)
- Filament built-in (Tailwind + Alpine + Livewire)
- خط ثيم مخصّص في `custom-styles.blade.php`
- Dark/Light mode عبر CSS variables (`--omar-*`)

### قاعدة البيانات
- **MySQL** (على السيرفر)
- يستهدف نحو **44 migration** و **33 Model** لتغطية كل الميزات أدناه

### البنية التحتية للتخزين والتوصيل (عالمي)
- **Cloudflare R2** كـ Object Storage الرئيسي لملفات التحميل (متوافق مع S3 API، **صفر رسوم egress**).
- **Cloudflare CDN** أمام التخزين لتوصيل سريع من أقرب edge للزائر.
- **Redis** للـ Cache والـ Queue.
- فصل المسارات: `r2://files/...` لملفات التحميل، والقرص المحلي/MediaLibrary للصور المصغّرة واللقطات.

---

## 2.5 محرّك الرفع العملاق (حتى 30GB) — القلب التقني للمشروع

> هذا أهم جزء في المشروع. الرفع العادي عبر Filament/PHP **يفشل** مع الملفات الكبيرة. التزم بالمعمارية التالية حرفيًا.

### المبدأ: الرفع المباشر إلى التخزين (Direct-to-Storage)
المتصفّح يرفع الملف **مباشرة إلى Cloudflare R2** عبر Presigned URLs، **دون أن يمرّ بايت واحد عبر سيرفر PHP**. هذا ما يجعله يتحمّل 30GB بسرعة وثبات.

### تدفّق الرفع (Upload Flow)
1. **بدء جلسة رفع:** المستخدم في لوحة الرفع يختار الملف → الواجهة (Uppy / tus client) تطلب من الـ backend بدء **Multipart Upload** على R2.
2. **الـ backend** ينشئ سجل `upload_session` (الحالة = `pending`) ويُرجع **Presigned URLs لكل جزء (part)**.
3. **التقطيع:** الواجهة تقسّم الملف لأجزاء (5–10MB لكل جزء) وترفعها **بالتوازي** مباشرة إلى R2.
4. **الاستئناف (Resumable):** عند انقطاع الإنترنت، يُكمل من آخر جزء ناجح — لا يبدأ من الصفر. شريط تقدّم دقيق + سرعة + وقت متبقٍ.
5. **الإكمال:** بعد رفع كل الأجزاء، الواجهة تُبلّغ الـ backend → يُنفّذ `CompleteMultipartUpload` على R2 → الحالة = `uploaded`.
6. **المعالجة الخلفية (Queue Job):**
   - توليد **checksum** (SHA-256 / MD5) للملف على R2.
   - **فحص فيروسات async** (ClamAV أو VirusTotal API) — لا يُنشر للجمهور قبل اجتياز الفحص.
   - استخراج بيانات الملف (الحجم الفعلي، النوع، التوقيع).
   - عند النجاح: الحالة = `published` ويظهر زر التحميل للجمهور.

### متطلّبات تقنية إلزامية
- **بدون مرور الملف على PHP:** لا `upload_max_filesize` ولا `post_max_size` ولا `client_max_body_size` تُقيّد ملف التحميل (تُقيّد فقط طلبات الـ API الصغيرة).
- **مكتبة الواجهة:** استخدم **Uppy** مع plugin الـ AWS S3 Multipart (متوافق مع R2)، أو tus.
- **R2 disk:** عرّف disk باسم `r2` في `config/filesystems.php` عبر `s3` driver مع `endpoint` الخاص بـ R2.
- **روابط التحميل للجمهور:** **Presigned GET URLs مؤقّتة** (تنتهي خلال دقائق) تُولّد عند الضغط على «تحميل» — تمنع الـ hotlinking وتُحصي التحميلات.
- **الأمان:** Rate limit على بدء جلسات الرفع، التحقّق من الامتدادات/الحجم الأقصى (30GB)، وربط كل جلسة بمستخدم مصرّح له (دور Author فأعلى).
- **التنظيف:** Job دوري يحذف جلسات الرفع غير المكتملة (`pending` أقدم من 24 ساعة) من R2 لتفادي تراكم الأجزاء اليتيمة.

### جدول `upload_sessions` (مقترح)
`id, user_id, software_id (nullable), original_name, mime_type, size_bytes, r2_key, r2_upload_id, parts_total, parts_completed, status (pending|uploaded|scanning|published|failed), checksum_sha256, scan_result, error_message, expires_at, timestamps`.

### لوحة الرفع (Upload Dashboard) المنفصلة
- صفحة Filament مخصّصة (أو Livewire component) للرافعين، فيها مكوّن Uppy، قائمة جلسات الرفع الجارية/السابقة، وحالاتها لحظيًا.
- بعد اكتمال الرفع، نموذج لإكمال بيانات المحتوى (الاسم، الفئة، النوع، الوصف، اللقطات…) ثم الإرسال للمراجعة/النشر.

---

## 3. الميزات الأساسية (الموقع العام)

### 3.1 الصفحة الرئيسية
- Hero بحث ضخم في المنتصف مع اقتراحات فورية (Alpine + Livewire).
- أقسام: «الأكثر تحميلًا»، «أُضيف حديثًا»، «محرّر يوصي به»، «التطبيقات المميزة».
- شرائح للفئات الرئيسية بأيقونات.
- عدّاد مباشر لإجمالي البرامج والتحميلات.

### 3.2 تصفّح البرامج
- صفحة فئات (Categories) متداخلة (فئة رئيسية ← فرعية).
- فلترة: حسب النظام (Windows / macOS / Linux / Android / iOS)، الترخيص (مجاني / تجريبي / مفتوح المصدر / مدفوع)، اللغة، الحجم، الإصدار.
- ترتيب: الأحدث / الأكثر تحميلًا / الأعلى تقييمًا / أبجديًا.
- ترقيم صفحات (Pagination) سلس.

### 3.3 صفحة البرنامج (Product Page)
- شعار البرنامج، الاسم، المطوّر، الإصدار الحالي، تاريخ التحديث.
- وصف غني (Rich text)، لقطات شاشة (Gallery)، فيديو اختياري.
- جدول المتطلبات (نظام التشغيل، المساحة، المعالج…).
- زر تحميل واضح + بدائل (مرايا / إصدارات سابقة).
- معلومات الأمان: حجم الملف، الـ checksum (MD5/SHA-256)، شارة «خالٍ من الفيروسات».
- تقييمات ومراجعات المستخدمين (نجوم + تعليقات).
- البرامج ذات الصلة، وبرامج من نفس المطوّر.

### 3.4 صفحة التحميل (Download Gateway)
- صفحة وسيطة بعدّاد تنازلي قصير قبل بدء التحميل.
- تسجيل كل عملية تحميل (Download log) للإحصائيات.
- دعم روابط مباشرة + روابط خارجية (External mirrors).

### 3.5 البحث
- بحث فوري (Live search) في الاسم والوصف والمطوّر.
- نتائج مصنّفة حسب الفئة.
- تسجيل عبارات البحث الشائعة لاستخدامها في التحسين.

### 3.6 صفحات إضافية
- المدوّنة / الأخبار التقنية.
- صفحة المطوّر (Developer profile) بكل برامجه.
- صفحات ثابتة (من نحن، سياسة الخصوصية، شروط الاستخدام، اتصل بنا).
- نظام تواصل (Contact form) مع حماية من السبام.

### 3.7 حسابات المستخدمين (اختياري لكن موصى به)
- تسجيل / دخول.
- قائمة المفضّلة (Wishlist / Bookmarks).
- سجل التحميلات الشخصي.
- إمكانية كتابة المراجعات والتقييم.

---

## 4. لوحة الإدارة (Filament 3.3)

### 4.1 الأدوار والصلاحيات (Spatie Permission)
- **Super Admin:** صلاحية كاملة.
- **Editor:** إدارة البرامج والفئات والمراجعات.
- **Author:** إضافة برامج/مقالات تخضع للمراجعة.
- **Moderator:** مراجعة التعليقات والتقييمات.

### 4.2 موارد Filament (Resources)
- إدارة البرامج (CRUD كامل + رفع الملفات والصور عبر MediaLibrary).
- إدارة الفئات والفئات الفرعية.
- إدارة المطوّرين/الناشرين.
- إدارة الإصدارات (Versions) لكل برنامج مع سجل تغييرات (Changelog).
- إدارة المراجعات والتعليقات (موافقة / رفض).
- إدارة المستخدمين والأدوار.
- إدارة المقالات والصفحات الثابتة.
- إدارة الإعلانات/البانرات.

### 4.3 لوحة المعلومات (Dashboard Widgets)
- إجمالي البرامج، التحميلات، المستخدمين، المراجعات.
- مخطط التحميلات (يومي / أسبوعي / شهري).
- أكثر البرامج تحميلًا، أحدث المراجعات، أكثر عبارات البحث.
- البرامج بانتظار المراجعة.

### 4.4 التصميم
- ثيم مخصّص عبر `custom-styles.blade.php`.
- متغيّرات CSS بادئة `--omar-*` للألوان.
- Dark / Light mode.

---

## 5. هيكل قاعدة البيانات (نماذج مقترحة)

> الهدف ~33 Model. هذه الجداول الأساسية، أضف جداول الربط (pivot) لتصل للعدد المطلوب.

**المحتوى الأساسي:**
`users`, `software`, `categories`, `developers`, `software_versions`, `screenshots`, `system_requirements`, `download_links`, `tags`, `software_tag` (pivot), `upload_sessions`.

> **أنواع المحتوى (Content Types):** جدول `software` هو الأساس ويحمل عمود `content_type` enum: `application` (برامج/تطبيقات) | `script` (سكربتات برمجية) | `template` (قوالب مواقع) | `plugin` (إضافات/ملحقات). الحقول الخاصة بكل نوع:
> - **script:** `programming_language`, `framework`, `license_type`, رابط مستودع اختياري.
> - **template:** `demo_url` (معاينة حيّة), `tech_stack`, `responsive` (bool), `categories` للقالب (تجاري/مدونة/متجر…).
> - **plugin:** `platform` (WordPress/…), `compatible_versions`, `requires`.
> - **application:** `system_requirements`, `os_support` كما في الأصل.
>
> استخدم Eloquent **single-table مع `content_type`** + حقول JSON (`meta`) للخصائص المتغيّرة، أو علاقات منفصلة حسب الحاجة. واجهة العرض والفلترة تتكيّف حسب النوع.

**التفاعل:**
`reviews`, `ratings`, `comments`, `bookmarks`, `download_logs`, `search_queries`.

**المحتوى الإضافي:**
`articles`, `article_categories`, `pages`, `banners`, `faqs`, `contacts`, `newsletter_subscribers`.

**النظام والصلاحيات (Spatie):**
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

**الإعدادات والوسائط:**
`settings`, `media` (MediaLibrary), `activity_log`, `personal_access_tokens` (Sanctum), `sessions`, `jobs`, `failed_jobs`.

**التعدّد اللغوي (i18n):**
- الواجهة عبر ملفات `lang/en/*.php` و `lang/ar/*.php` (الإنجليزية افتراضية).
- المحتوى الديناميكي القابل للترجمة (اسم/وصف البرنامج، الفئات) عبر **spatie/laravel-translatable** (أعمدة JSON `{"en": "...", "ar": "..."}`) — قابل للتوسّع لأي لغة دون migration جديد.
- `dir` يُحسب تلقائيًا من اللغة الحالية (`ar` → rtl، غيرها → ltr)، ومحوّل لغة في الـ header.

### العلاقات الرئيسية
- `Software` ينتمي إلى `Category` و `Developer`، وله عدّة `Versions` و `Screenshots` و `Reviews` و `DownloadLinks`، وعلاقة many-to-many مع `Tags`.
- `User` له عدّة `Reviews` و `Bookmarks` و `DownloadLogs`.
- `Category` self-referencing (parent_id) للفئات الفرعية.

---

## 6. نظام التصميم (Design System) — إلزامي

> هذا نظام التصميم الوحيد المعتمد. التزم به حرفيًا ولا تستخدم أي تقنية أو نمط خارجه.

### 6.1 فلسفة التصميم
هوية **فاخرة سعودية**: أخضر سعودي + ذهبي ملكي + أسود فاخر + لمسة عربية (خطوط Cairo/Tajawal، زخارف، RTL). المعادلة: **فخامة + مهنية + ثقة + هوية سعودية**.

### 6.2 التقنيات (هذه فقط — لا غيرها)
| التقنية | الإصدار | طريقة التحميل |
|---------|---------|----------------|
| **Tailwind CSS** | 3.x | CDN (`cdn.tailwindcss.com`) — بدون build step |
| **Alpine.js** | 3.x | CDN (`cdn.jsdelivr.net`) |
| **Font Awesome** | 6.5.0 | CDN (`cdnjs.cloudflare.com`) |
| **Google Fonts** | — | CSS link |

**ممنوع منعًا باتًا:** Vite/Webpack، Node modules للموقع، React/Vue، Sass/PostCSS. كل شيء **Blade + Alpine + CSS variables**، وأي تعديل في `.blade.php` يظهر فورًا بدون compile.

### 6.3 نظام الألوان (CSS Variables)
تُعرَّف في `partials/theme.blade.php` وتقبل التخصيص من لوحة الإدارة:

| المتغيّر | القيمة الافتراضية | الاستخدام |
|----------|------------------|-----------|
| `--color-primary` | `#006C35` 🟢 | الأخضر السعودي — أزرار رئيسية، روابط، عناوين |
| `--color-secondary` | `#C9A961` 🪙 | الذهبي الملكي — تأكيدات، حدود، أيقونات مميّزة |
| `--color-accent` | `#8B6F47` 🟫 | البرونزي — لمسات ثانوية |
| `--color-dark` | `#1A1A1A` ⬛ | الأسود الفاخر — نصوص، فوتر |
| `--color-success` | `#16a34a` | تأكيدات إيجابية (مثلًا شارة Malware-free) |
| `--color-danger` | `#dc2626` | أخطاء وتحذيرات |
| Background main | `#FBFAF6` | خلفية الموقع (off-white ناعمة) |

**ألوان Tailwind المخصّصة** (في `tailwind.config.colors` داخل `app.blade.php`):
```js
'saudi-green':      'rgb(var(--color-primary-rgb)   / <alpha-value>)',
'saudi-green-dark': 'var(--color-primary-dark)',
'royal-gold':       'rgb(var(--color-secondary-rgb) / <alpha-value>)',
'royal-gold-dark':  'var(--color-secondary-dark)',
'luxury-black':     'rgb(var(--color-dark-rgb)      / <alpha-value>)',
'bronze':           'rgb(var(--color-accent-rgb)    / <alpha-value>)',
'classic-gray':     '#F5F5F5',
```
- الشفافية تعمل تلقائيًا: `bg-saudi-green/20`، `text-royal-gold/80`.
- المتغيّرات الداكنة تُحسب تلقائيًا: `--color-primary-dark: color-mix(in srgb, var(--color-primary) 72%, #000);`

### 6.4 الخطوط (Typography)
تُحمَّل من Google Fonts مع `display=swap` و `preconnect`:

| الخط | الأوزان | الاستخدام |
|------|---------|-----------|
| **Tajawal** | 300–900 | النصوص الأساسية (body) |
| **Cairo** | 400–900 | العناوين (h1–h5) |
| **Playfair Display** | 400/700/900 | لمسات لاتينية فاخرة (شعارات/توقيعات) |

```css
body              { font-family: 'Tajawal','Cairo',sans-serif; }
h1,h2,h3,h4,h5    { font-family: 'Cairo','Tajawal',sans-serif; }
```
مقاسات نموذجية: `font-cairo text-3xl md:text-4xl lg:text-5xl font-black` للعناوين الكبرى.

### 6.5 المكوّنات الجاهزة (Component Classes)
عرّفها في `app.blade.php` واستخدمها بدل كتابة gradient/shadow يدويًا:

- `.btn-primary` — أخضر سعودي متدرّج (Action رئيسي مثل «تحميل»)، مع ظل أخضر و hover lift.
- `.btn-gold` — ذهبي ملكي (CTA مميّز مثل «النسخة المدفوعة/Pro»).
- `.btn-outline` — حدود ذهبية شفّافة تمتلئ عند hover (Action ثانوي).
- `.card-luxury` — البطاقة الفاخرة الافتراضية: خلفية بيضاء، حدود ذهبية `.18`، `rounded-2xl`، ظل أخضر خفيف، ترتفع 4px عند hover. **هي بطاقة البرنامج القياسية**.
- `.gold-divider` — فاصل أنيق مع نقطة ماسية، لفصل أقسام الصفحة.
- `.arabic-arch` — قوس زخرفي ذهبي تحت العنوان.

### 6.6 الزوايا والظلال
- نصف القطر: `rounded-lg` (حقول/أزرار) → `rounded-2xl` (`.card-luxury`) → `rounded-3xl` (Hero glass).
- ظلال مخصّصة في Tailwind config:
```js
'luxury': '0 20px 60px -15px rgb(var(--color-primary-rgb) / .25)',  // أخضر
'gold':   '0 10px 30px -10px rgb(var(--color-secondary-rgb) / .4)', // ذهبي
```

### 6.7 الأنماط الزخرفية
- `.arabesque-bg` — هالتان شعاعيّتان شبه شفّافتين (ذهبية + خضراء) في زوايا متقابلة.
- `.hero-pattern` — تدرّج أخضر-أسود + نقش معيّنات (zellige SVG) ذهبية للـ Hero.
- `.hero-glass` — Glassmorphism: `backdrop-filter: blur(10px)` + حدود ذهبية شفّافة + ظل عميق.
- `::selection` — خلفية ذهبية ونص أسود عند تحديد النص.

### 6.8 الحركات
- `.float-anim` — تطفّو لطيف (للزر العائم / أيقونات Hero): `translateY(-8px)` بدورة 4s.
- Hover lift على الأزرار والبطاقات: `transition:.3s ease` + `translateY(-2px)` + ظل أعمق.

### 6.9 الوضع الليلي (Dark Mode)
- **الموقع العام:** بدون dark mode (التصميم off-white فاخر لا يحتاجه للزوار).
- **لوحة الإدارة (Filament):** يدعم `light / dark / auto`، زر تبديل في الـ top bar، يُخزَّن في `localStorage`، وكل الـ tokens (`--omar-*`) تتبدّل تلقائيًا.

### 6.10 Design Tokens للوحة الإدارة (`--omar-*`)
استخدمها في أي widget مخصّص بدل القيم الثابتة ليعمل dark mode تلقائيًا:

| Token | Light | Dark | الاستخدام |
|-------|-------|------|-----------|
| `--omar-surface` | `#fff` | `#1a2027` | خلفية البطاقات |
| `--omar-surface-2` | `#f9fafb` | `#232b33` | خلفية ثانوية |
| `--omar-text` | `#1a1a1a` | `#e5e7eb` | نص رئيسي |
| `--omar-text-muted` | `#6b7280` | `#9ca3af` | نص ثانوي |
| `--omar-border` | `rgba(201,169,97,.20)` | `rgba(201,169,97,.35)` | حدود ذهبية |
| `--omar-shadow-card` | `0 4px 18px -8px rgba(0,108,53,.10)` | `0 8px 24px -10px rgba(0,0,0,.55)` | ظل البطاقات |

**قاعدة:** في widgets الإدارة استخدم `var(--omar-text)` بدل `#1a1a1a` — لا تكتب ألوانًا ثابتة.

### 6.11 ثيم Filament
```php
->colors(['primary' => Color::hex($primaryHex), 'gold' => Color::hex($accentHex)])
->font('Tajawal')
->brandLogo(asset('logo/logo-dark.png'))
->maxContentWidth(MaxWidth::Full)
```
مع `custom-styles.blade.php` (sidebar بتدرّج ذهبي، جداول stripes ناعمة، scrollbar ذهبي رفيع، ظلال أعمق في dark)، وصفحة `/admin/settings/theme` لتخصيص الألوان حيًّا.

### 6.12 الاتجاه (LTR/RTL) والتجاوب
- **الافتراضي عالمي:** `<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">` — الإنجليزية (ltr) افتراضيًا، والعربية تُفعّل rtl تلقائيًا.
- استخدم خصائص logical حصريًا: `me-*` / `ms-*` بدل `mr-*` / `ml-*` ليعمل التصميم في الاتجاهين دون تكرار.
- الأرقام/الأحجام/الإصدارات تبقى `dir="ltr"` دائمًا في أي لغة.
- Mobile-first؛ شبكة بطاقات البرامج تتكيّف: 1 → 2 → 4 أعمدة (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`).
- Hero: `min-height:92vh; max-height:780px`.

### 6.13 مكوّنات Blade وPartials
- `<x-sar />` — رمز الريال السعودي الرسمي عبر CSS mask (يرث لون النص)؛ استخدمه لأسعار البرامج المدفوعة بدل كتابة «ر.س».
- Partials: `partials.header`, `partials.footer`, `partials.theme`, `partials.cookie-consent`, `partials.pwa-install`, `partials.occasion-banner` (بانر المناسبات: اليوم الوطني، رمضان…).
- لموقع التحميل أضِف: `partials.download-search` (شريط البحث)، وبطاقة برنامج موحّدة بـ `.card-luxury`.

### 6.14 PWA
- `manifest.json`: `theme_color: #006C35`, `background_color: #0F1419`.
- meta `theme-color` لوضعَي light/dark، أيقونات 72px→512px (اثنتان maskable)، و`apple-touch-icon` 180×180.

### 6.15 أفضل الممارسات
**افعل:** استخدم CSS variables بدل القيم المباشرة؛ استخدم component classes (`.btn-primary`, `.card-luxury`)؛ استخدم `--omar-*` في widgets الإدارة؛ اكتب `width`/`height` على كل صورة؛ استخدم `<x-sar />`.
**لا تفعل:** لا inline `style="color:#006C35"`؛ لا gradient/shadow يدوي بدل المكوّنات الجاهزة؛ لا `#fff`/`#1a1a1a` في الإدارة؛ لا خطوط جديدة دون تحديث الـ stack؛ لا `transition: all` على الـ body.
**منع اهتزاز التخطيط:** `html { scrollbar-gutter: stable; }` + أبعاد الصور + `font-display: swap`.

### 6.16 أفكار بصرية مميّزة لموقع التحميل
- شارات ثقة بألوان النظام: «موثّق» (ذهبي)، «خالٍ من الفيروسات» (أخضر success)، «اختيار المحرّر» (شارة ذهبية).
- بطاقة برنامج `.card-luxury` بأيقونة كبيرة + اسم + تقييم نجوم + عدّاد تحميلات.
- مقارنة برنامجين جنبًا إلى جنب.
- خيار «نسخة محمولة (Portable)» منفصل.
- QR code لتطبيقات Android/iOS للتحميل على الجوال مباشرة.

---

## 7. الأداء والأمان والـ SEO

**الأداء:**
- Cache للصفحات الثابتة والقوائم.
- Eager loading لتجنّب مشكلة N+1.
- ضغط الصور تلقائيًا (Conversions في MediaLibrary).

**الأمان:**
- Rate limiting على البحث والتحميل والـ API.
- التحقق من نوع وحجم الملفات المرفوعة + توليد checksum.
- CSRF protection، Validation صارم، تنظيف الـ HTML في المحتوى الغني.
- Sanctum لحماية الـ API endpoints.

**الـ SEO:**
- روابط لطيفة (Slugs) عربية/لاتينية.
- Meta tags و Open Graph و Twitter Cards ديناميكية لكل برنامج.
- Sitemap.xml + robots.txt.
- Schema.org (SoftwareApplication) لتحسين النتائج في جوجل.

---

## 8. الـ API (Sanctum)

نقاط نهاية (Endpoints) أساسية لتطبيق جوال مستقبلي:
- `GET /api/software` — قائمة مع فلترة وترقيم.
- `GET /api/software/{slug}` — تفاصيل برنامج.
- `GET /api/categories` — الفئات.
- `GET /api/search?q=` — بحث.
- `POST /api/reviews` — إضافة مراجعة (محمي بـ Sanctum).
- `POST /api/download/{id}` — تسجيل تحميل + إرجاع الرابط.

---

## 9. ما المطلوب منك إنتاجه

1. هيكل المشروع كاملًا (Migrations, Models, Relationships) بما فيه `content_type` و `upload_sessions` و i18n.
2. **محرّك الرفع العملاق (القسم 2.5):** إعداد R2 disk، خدمة Presigned Multipart، مكوّن Uppy، Queue jobs للـ checksum والفحص. **أعطه أولوية مبكّرة.**
3. موارد Filament للوحة الإدارة مع الصلاحيات + **لوحة الرفع المنفصلة**.
4. واجهات Blade للموقع العام (LTR افتراضي + RTL، Tailwind CDN، Alpine، i18n).
5. منطق البحث والفلترة والتحميل (Presigned GET مؤقّتة + Download log).
6. Seeders ببيانات تجريبية واقعية (فئات + محتوى متعدّد الأنواع + مطوّرين).
7. توثيق مختصر لكيفية التشغيل (`README`) شامل إعداد R2 و Redis و Queue worker.

ابدأ بمخطّط قاعدة البيانات والـ Migrations، ثم **محرّك الرفع والتخزين (R2 + Multipart + Queue)** لأنه القلب التقني، ثم الـ Models والعلاقات، ثم لوحة Filament ولوحة الرفع، وأخيرًا واجهات الموقع العام. اشرح كل خطوة باختصار قبل تنفيذها.
