<?php

return [
    // Security events log
    'events_nav' => 'أحداث الأمان',
    'events_single' => 'حدث أمني',
    'events_plural' => 'أحداث الأمان',
    'ip' => 'عنوان IP',
    'type' => 'النوع',
    'severity' => 'الخطورة',
    'method' => 'الطريقة',
    'path' => 'المسار',
    'detail' => 'التفاصيل',
    'member' => 'العضو',
    'guest' => 'زائر',
    'country' => 'الدولة',
    'agent' => 'المتصفّح',
    'when' => 'الوقت',
    'blocked_flag' => 'حُظِر؟',
    'events_empty' => 'لا توجد أحداث أمنية — كل شيء هادئ. 🛡️',

    'sev' => [
        'critical' => 'حرِج',
        'high' => 'عالٍ',
        'medium' => 'متوسّط',
        'low' => 'منخفض',
    ],
    'types' => [
        'sqli' => 'حقن SQL',
        'xss' => 'XSS',
        'traversal' => 'اجتياز مسارات',
        'lfi' => 'تضمين ملفات',
        'rce' => 'تنفيذ أوامر',
        'scanner_ua' => 'أداة فحص',
        'honeypot' => 'مصيدة شرفية',
        'bruteforce' => 'تخمين دخول',
    ],

    'filter_severity' => 'الخطورة',
    'filter_type' => 'النوع',
    'block_ip' => 'حظر هذا العنوان',
    'block_ip_done' => 'تم حظر العنوان.',

    // Blocked IPs
    'blocked_nav' => 'العناوين المحظورة',
    'blocked_single' => 'عنوان محظور',
    'blocked_plural' => 'العناوين المحظورة',
    'reason' => 'السبب',
    'auto' => 'تلقائي',
    'manual' => 'يدوي',
    'source' => 'المصدر',
    'hits' => 'مرّات المحاولة',
    'expires' => 'ينتهي',
    'permanent' => 'دائم',
    'expired' => 'منتهٍ',
    'unblock' => 'رفع الحظر',
    'blocked_empty' => 'لا توجد عناوين محظورة.',
    'add_block_hint' => 'أدخل عنوان IP لحظره يدويًا (حظر دائم).',

    // Auto-generated reasons / alerts / block page
    'reason_auto' => 'حظر تلقائي: :type',
    'reason_bruteforce' => 'حظر تلقائي: محاولات دخول فاشلة متكررة',
    'alert_title' => '🛡️ تم حظر مهاجم تلقائيًا',
    'alert_body' => 'نوع الهجوم: :type — العنوان: :ip',
    'alert_open' => 'عرض أحداث الأمان',

    'blocked_title' => 'تم حظر الوصول',
    'blocked_body' => 'رُصد نشاط غير طبيعي من اتصالك ومُنِع الوصول مؤقتًا حمايةً للموقع.',
    'blocked_contact' => 'إن كنت تعتقد أن هذا خطأ، تواصل معنا وسنراجع الأمر.',
];
