<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Software;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The integrated Saudi accounting ERP Flutter app, with a live web preview.
 * updateOrCreate by slug — safe to re-run.
 */
class AccountingErpProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = Category::where('slug', 'flutter')->value('id');
        $userId = User::orderBy('id')->value('id');

        Software::updateOrCreate(
            ['slug' => 'accounting-erp'],
            [
                'content_type' => 'mobile_app',
                'category_id' => $categoryId,
                'user_id' => $userId,
                'name' => [
                    'ar' => 'نظام المحاسبة المتكامل — ERP سعودي',
                    'en' => 'Integrated Accounting System — Saudi ERP',
                ],
                'short_description' => [
                    'ar' => 'نظام محاسبة وتخطيط موارد متكامل بواجهة عربية: فواتير، تقارير، رسوم بيانية، وتقويم هجري — يعمل على الجوال والويب.',
                    'en' => 'An integrated accounting & ERP system with an Arabic UI: invoices, reports, charts and a Hijri calendar — on mobile and web.',
                ],
                'description' => [
                    'ar' => '<p>نظام محاسبة متكامل (ERP) مبنيّ بـFlutter بواجهة عربية احترافية: إدارة الفواتير والعملاء، تقارير مالية ورسوم بيانية تفاعلية، جداول بيانات متقدّمة، توليد PDF وطباعة، ودعم التقويم الهجري — لمنشآت السوق السعودي.</p><p>جرّب <strong>النسخة الحيّة</strong> أعلاه مباشرةً في متصفّحك دون تثبيت.</p>',
                    'en' => '<p>An integrated accounting/ERP system built with Flutter and a professional Arabic UI: invoicing and clients, financial reports and interactive charts, advanced data tables, PDF generation and printing, and Hijri calendar support — for Saudi businesses.</p><p>Try the <strong>live preview</strong> above right in your browser.</p>',
                ],
                'os_support' => ['android', 'ios', 'web'],
                'languages' => ['ar', 'en'],
                'license_type' => 'free',
                'current_version' => '1.0.0',
                'is_malware_free' => true,
                'is_featured' => true,
                'status' => 'published',
                'published_at' => Carbon::now(),
                'live_preview_url' => '/app-previews/my-system/',
            ]
        );
    }
}
