<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SoftwareController;
use App\Http\Controllers\Upload\MultipartUploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

// AdSense verification file (generated from the publisher id in Site settings → Ads).
Route::get('/ads.txt', function () {
    $txt = app(\App\Support\Ads::class)->adsTxt();
    abort_if(! $txt, 404);

    return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('ads.txt');

// SEO: dynamic sitemap + robots (point crawlers to the sitemap).
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    return response(implode("\n", [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /upload',
        'Disallow: /go/',
        'Disallow: /d/',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

Route::get('/browse', [BrowseController::class, 'index'])->name('browse');

// Learn & Build — interactive student hub
Route::get('/learn', [LearnController::class, 'index'])->name('learn');
Route::get('/learn/videos', [LearnController::class, 'videos'])->name('learn.videos');
Route::get('/learn/lab/{lab}', [LearnController::class, 'lab'])->name('learn.lab');
Route::get('/learn/{category}', [LearnController::class, 'category'])->name('learn.category');

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/api/search/live', [SearchController::class, 'live'])
    ->middleware('throttle:60,1')->name('search.live');

// Blog / articles
Route::get('/blog', [ArticleController::class, 'index'])->name('blog.index');
Route::get('/blog/{article}', [ArticleController::class, 'show'])->name('blog.show');

// Contact + newsletter
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')->name('contact.store');
Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:5,1')->name('newsletter.store');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

// Human-readable sitemap (the XML one for crawlers stays at /sitemap.xml).
Route::get('/sitemap', [\App\Http\Controllers\SitemapController::class, 'html'])->name('sitemap.html');

// File-formats reference guide.
Route::get('/formats', [\App\Http\Controllers\FormatController::class, 'index'])->name('formats.index');

// Legal report pages (DMCA + content abuse) — info + a form → support ticket.
Route::get('/dmca', [\App\Http\Controllers\LegalController::class, 'dmca'])->name('dmca');
Route::get('/abuse', [\App\Http\Controllers\LegalController::class, 'abuse'])->name('abuse');
Route::post('/legal/report', [\App\Http\Controllers\LegalController::class, 'store'])
    ->middleware('throttle:5,1')->name('legal.report');

// Report a problem (members + guests) — lands as a support ticket with a screenshot.
Route::post('/report-problem', [\App\Http\Controllers\ProblemReportController::class, 'store'])
    ->middleware('throttle:6,1')->name('problem.report');

// Language switch (public site)
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
// Language switch (Filament panels — separate session key)
Route::get('/panel-locale/{locale}', [LocaleController::class, 'switchPanel'])->name('panel.locale.switch');

// Two-factor login challenge (for users who enabled an authenticator app).
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'store'])
        ->middleware('throttle:10,1')->name('two-factor.verify');
    Route::post('/two-factor/logout', [\App\Http\Controllers\TwoFactorController::class, 'logout'])->name('two-factor.logout');
});

// Download gateway (rate-limited) — keep ABOVE the {software} catch-all
Route::get('/download/{software}/{link}', [DownloadController::class, 'gateway'])
    ->name('download.gateway');
Route::get('/go/{software}/{link}', [DownloadController::class, 'start'])
    ->middleware('throttle:30,1')->name('download.start');

/*
|--------------------------------------------------------------------------
| Upload engine (multipart → R2). Session-authenticated uploaders only.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('upload/multipart')->name('upload.multipart.')->group(function () {
    Route::post('/create', [MultipartUploadController::class, 'create'])->name('create');
    Route::post('/sign', [MultipartUploadController::class, 'sign'])->name('sign');
    Route::post('/list-parts', [MultipartUploadController::class, 'listParts'])->name('list-parts');
    Route::post('/complete', [MultipartUploadController::class, 'complete'])->name('complete');
    Route::post('/abort', [MultipartUploadController::class, 'abort'])->name('abort');

    // Local-disk fallback: receive one raw chunk. Tamper-proofed by a signed URL
    // (Uppy PUTs the chunk directly, so it carries no CSRF token — see bootstrap/app.php).
    Route::put('/put-part/{session}', [MultipartUploadController::class, 'putPart'])
        ->middleware('signed')->name('put-part');
});

// Direct media upload (images & PDF) — public, hotlinkable, with share kit.
Route::middleware(['auth'])->post('/upload/media', [\App\Http\Controllers\Upload\MediaUploadController::class, 'store'])
    ->name('upload.media');

// Public shared-asset landing + download (/d/{slug}) — above the {software} catch-all.
Route::get('/d/{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('assets.show');
Route::post('/d/{asset}/unlock', [\App\Http\Controllers\AssetController::class, 'unlock'])
    ->middleware('throttle:10,1')->name('assets.unlock');
Route::get('/d/{asset}/download', [\App\Http\Controllers\AssetController::class, 'download'])
    ->middleware('throttle:60,1')->name('assets.download');

// Public member "creator" profile (/u/{username}) — their avatar, bio & public files.
Route::get('/u/{user:username}', [\App\Http\Controllers\MemberProfileController::class, 'show'])
    ->name('members.show');

// Clear application caches from the admin topbar (staff only).
Route::post('/system/clear-cache', function () {
    abort_unless(auth()->user()?->isStaff(), 403);
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Filament\Notifications\Notification::make()->success()->title(__('admin.cache_cleared'))->send();

    return back();
})->middleware('auth')->name('system.clear-cache');

// Maintenance-page preview for signed-in staff (the live toggle is in admin settings).
Route::get('/preview/maintenance', fn () => view('maintenance', \App\Support\MaintenancePage::data()))
    ->middleware('auth')->name('maintenance.preview');

// Public comments on a product
Route::post('/software/{software}/comments', [\App\Http\Controllers\CommentController::class, 'store'])
    ->middleware('throttle:5,1')->name('comments.store');

// Public star reviews/ratings on a product (moderated)
Route::post('/software/{software}/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])
    ->middleware('throttle:5,1')->name('reviews.store');

// Isolated 3D preview (embedded as an iframe in the admin form). Auth-only.
Route::get('/model-preview/{software}', function (\App\Models\Software $software) {
    abort_unless(auth()->check(), 403);
    abort_unless($software->has3dModel(), 404);

    return view('model-preview-embed', compact('software'));
})->name('model.preview');

// Product page — slug catch-all, registered last so it can't shadow the above.
Route::get('/software/{software}', [SoftwareController::class, 'show'])->name('software.show');

// Static pages by slug. The negative lookahead keeps this from swallowing the
// Filament panels (/admin, /upload), Livewire, storage, or any named route above.
Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', '^(?!admin|upload|api|livewire|filament|storage|build|go|download|software|browse|search|blog|contact|newsletter|locale|learn|up).+')
    ->name('page.show');
