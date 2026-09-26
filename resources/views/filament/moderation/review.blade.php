<x-filament-panels::page>
@php
    $fmt = function ($b) {
        $b = (int) $b;
        if ($b <= 0) return '0 B';
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
    };
    $ext = strtolower(pathinfo((string) $asset->original_name, PATHINFO_EXTENSION));
    $status = $asset->moderation_status;
    $tone = ['pending' => '#d97706', 'approved' => '#059669', 'rejected' => '#dc2626'][$status] ?? '#6b7280';
    $toneIcon = ['pending' => 'fa-hourglass-half', 'approved' => 'fa-circle-check', 'rejected' => 'fa-ban'][$status] ?? 'fa-circle';

    [$fileIcon, $fileHex] = match (true) {
        $asset->isPdf() => ['fa-file-pdf', '#dc2626'],
        in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz']) => ['fa-file-zipper', '#d97706'],
        in_array($ext, ['exe', 'msi', 'dmg', 'pkg', 'deb', 'rpm', 'appimage']) => ['fa-window-maximize', '#0284c7'],
        in_array($ext, ['apk', 'aab', 'ipa']) => ['fa-mobile-screen', '#16a34a'],
        in_array($ext, ['iso', 'img', 'bin']) => ['fa-compact-disc', '#4f46e5'],
        in_array($ext, ['php', 'js', 'ts', 'py', 'rb', 'go', 'rs', 'java', 'jar', 'sql', 'json', 'xml', 'yml', 'yaml', 'env']) => ['fa-file-code', '#7c3aed'],
        in_array($ext, ['obj', 'fbx', 'gltf', 'glb', 'stl', 'blend', 'max', 'c4d', '3dm', 'skp', '3ds', 'dae', 'usdz']) => ['fa-cube', '#0d9488'],
        default => ['fa-file', '#6b7280'],
    };
    $isArchive = in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz'], true);

    $scan = $asset->uploadSession?->scan_result;
    $scanRaw = $asset->uploadSession?->scan_report;
    $scanStats = is_string($scanRaw) ? json_decode($scanRaw, true) : null;
    $scanTone = ['clean' => '#059669', 'infected' => '#dc2626', 'error' => '#d97706'][$scan] ?? '#6b7280';
    $scanIcon = ['clean' => 'fa-shield-halved', 'infected' => 'fa-virus', 'error' => 'fa-triangle-exclamation'][$scan] ?? 'fa-circle-question';

    $histIcon = [
        'approved' => ['fa-circle-check', '#059669'], 'auto_approved' => ['fa-wand-magic-sparkles', '#0284c7'],
        'rejected' => ['fa-ban', '#dc2626'], 'restored' => ['fa-rotate-left', '#d97706'],
        'reported' => ['fa-flag', '#dc2626'], 'downloaded' => ['fa-download', '#6b7280'],
        'owner_trusted' => ['fa-user-check', '#0284c7'], 'owner_untrusted' => ['fa-user', '#6b7280'],
        'owner_banned' => ['fa-user-slash', '#dc2626'], 'owner_unbanned' => ['fa-user-check', '#059669'],
        'purged' => ['fa-trash', '#6b7280'],
    ];
    $diskLabel = ['r2' => 'Object storage (S3)', 'local' => 'Server (private)', 'public' => 'Server (public)'][$asset->disk] ?? $asset->disk;
@endphp

<style>
    .fm-grid { display:grid; grid-template-columns:minmax(0, 1.65fr) minmax(0, 1fr); gap:1.25rem; align-items:start; }
    @media (max-width: 1100px) { .fm-grid { grid-template-columns:minmax(0, 1fr); } }
    .fm-col { display:flex; flex-direction:column; gap:1.25rem; min-width:0; }
    .fm-card { border:1px solid rgba(128,128,128,.16); border-radius:1.1rem; box-shadow:0 8px 24px -18px rgba(0,0,0,.35); overflow:hidden; }
    .fm-head { display:flex; align-items:center; gap:.55rem; padding:.85rem 1.15rem; border-bottom:1px solid rgba(128,128,128,.12); font-size:.82rem; font-weight:800; }
    .fm-head i { color:#006C35; }
    .fm-body { padding:1.1rem 1.15rem; }
    .fm-facts { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.9rem 1.2rem; }
    @media (max-width: 640px) { .fm-facts { grid-template-columns:minmax(0, 1fr); } }
    .fm-k { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; opacity:.55; margin-bottom:.2rem; }
    .fm-v { font-size:.84rem; font-weight:600; word-break:break-word; }
    .fm-chip { display:inline-flex; align-items:center; gap:.3rem; border-radius:9999px; padding:.18rem .6rem; font-size:.68rem; font-weight:700; }
    .fm-stats { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); border-top:1px solid rgba(128,128,128,.12); }
    .fm-stats > div { padding:.75rem .5rem; text-align:center; }
    .fm-stats > div + div { border-inline-start:1px solid rgba(128,128,128,.12); }
    .fm-num { font-size:1.05rem; font-weight:800; }
    .fm-lbl { font-size:.66rem; opacity:.55; margin-top:.1rem; }
    .fm-checker { background-color:#f3f4f6; background-image:linear-gradient(45deg,#e5e7eb 25%,transparent 25%),linear-gradient(-45deg,#e5e7eb 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#e5e7eb 75%),linear-gradient(-45deg,transparent 75%,#e5e7eb 75%); background-size:22px 22px; background-position:0 0,0 11px,11px -11px,-11px 0; }
    .fm-tl { position:relative; padding-inline-start:1.6rem; }
    .fm-tl::before { content:''; position:absolute; inset-block:.4rem; inset-inline-start:.55rem; width:2px; background:rgba(128,128,128,.18); border-radius:2px; }
    .fm-tl-item { position:relative; padding-bottom:1rem; }
    .fm-tl-dot { position:absolute; inset-inline-start:-1.6rem; top:0; display:flex; height:1.15rem; width:1.15rem; align-items:center; justify-content:center; border-radius:9999px; color:#fff; font-size:.55rem; box-shadow:0 0 0 3px var(--fm-ring, #fff); }
    .dark .fm-tl-dot { --fm-ring: rgb(17 24 39); }
    .fm-row { display:flex; align-items:center; gap:.6rem; padding:.45rem .1rem; border-bottom:1px dashed rgba(128,128,128,.14); font-size:.78rem; }
    .fm-row:last-child { border-bottom:0; }
    .fm-mono { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.74rem; }
    .fm-copy { cursor:pointer; opacity:.6; } .fm-copy:hover { opacity:1; color:#006C35; }
</style>

{{-- ===== Status banner ===== --}}
<div class="fm-card bg-white dark:bg-gray-900" style="border-inline-start:5px solid {{ $tone }};">
    <div style="display:flex; align-items:center; gap:1rem; padding:1rem 1.2rem; flex-wrap:wrap;">
        <span style="display:flex; height:2.9rem; width:2.9rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:.9rem; font-size:1.2rem; color:{{ $tone }}; background:{{ $tone }}17;">
            <i class="fa-solid {{ $toneIcon }}"></i>
        </span>
        <div style="flex:1 1 16rem; min-width:0;">
            <div class="text-gray-950 dark:text-white" style="font-size:1rem; font-weight:800;">{{ __('moderation.status.'.$status) }}</div>
            <div class="text-gray-500 dark:text-gray-400" style="font-size:.78rem; margin-top:.15rem;">
                @if ($status === 'pending')
                    {{ __('moderation.review.uploaded') }} {{ $asset->created_at->diffForHumans() }}
                @elseif ($asset->reviewed_at)
                    {{ __('moderation.review.reviewed_by') }}
                    <strong>{{ $asset->reviewer?->name ?? __('moderation.review.system') }}</strong>
                    · <span dir="ltr">{{ $asset->reviewed_at->format('Y-m-d H:i') }}</span>
                @endif
            </div>
            @if ($asset->isRejected())
                <div style="margin-top:.55rem; display:flex; gap:.4rem; flex-wrap:wrap; align-items:center;">
                    <span class="fm-chip" style="background:#dc26261a; color:#dc2626;"><i class="fa-solid fa-gavel"></i> {{ __('moderation.reason.'.($asset->rejection_reason ?: 'other')) }}</span>
                    <span class="fm-chip" style="background:rgba(128,128,128,.12);"><i class="fa-regular fa-clock"></i> {{ __('moderation.review.retention', ['date' => $asset->reviewed_at?->copy()->addDays(\App\Support\FileModeration::RETENTION_DAYS)->format('Y-m-d')]) }}</span>
                </div>
                @if ($asset->rejection_note)
                    <div class="text-gray-600 dark:text-gray-300" style="margin-top:.5rem; font-size:.8rem; white-space:pre-line;">{{ $asset->rejection_note }}</div>
                @endif
            @endif
        </div>
        @if ($asset->open_reports > 0)
            <span class="fm-chip" style="background:#dc2626; color:#fff; padding:.4rem .8rem; font-size:.75rem;">
                <i class="fa-solid fa-flag"></i> {{ $asset->open_reports }} {{ __('moderation.tab.reported') }}
            </span>
        @endif
    </div>
</div>

<div class="fm-grid">
    {{-- ================= Main column ================= --}}
    <div class="fm-col">

        {{-- Preview --}}
        <div class="fm-card bg-white dark:bg-gray-900">
            <div class="fm-head text-gray-950 dark:text-white"><i class="fa-solid fa-eye"></i> {{ __('moderation.review.preview') }}</div>
            @if ($asset->isImage())
                <a href="{{ $asset->directUrl() }}" target="_blank" class="fm-checker" style="display:flex; justify-content:center; padding:1rem;">
                    <img src="{{ $asset->variantUrl('medium') }}" alt="" style="max-height:62vh; max-width:100%; border-radius:.5rem; box-shadow:0 10px 30px -15px rgba(0,0,0,.4);">
                </a>
            @elseif ($asset->isPdf())
                <iframe src="{{ $asset->directUrl() }}" style="display:block; width:100%; height:70vh; border:0;" title="{{ $asset->original_name }}"></iframe>
            @else
                <div style="display:flex; flex-direction:column; align-items:center; gap:.9rem; padding:2.6rem 1.5rem; text-align:center; background:linear-gradient(180deg, {{ $fileHex }}0d, transparent);">
                    <span style="display:flex; height:5.2rem; width:5.2rem; align-items:center; justify-content:center; border-radius:1.4rem; font-size:2.4rem; color:{{ $fileHex }}; background:{{ $fileHex }}17; border:1px solid {{ $fileHex }}33;">
                        <i class="fa-solid {{ $fileIcon }}"></i>
                    </span>
                    <div>
                        <div class="text-gray-950 dark:text-white" style="font-size:1rem; font-weight:800; word-break:break-all;">{{ $asset->original_name }}</div>
                        <div class="text-gray-500" style="font-size:.8rem; margin-top:.25rem;" dir="ltr">{{ $fmt($asset->size_bytes) }} · {{ strtoupper($ext ?: $asset->kind) }}</div>
                    </div>
                    <a href="{{ route('moderation.download', $asset) }}" target="_blank"
                       style="display:inline-flex; align-items:center; gap:.45rem; border-radius:.7rem; padding:.55rem 1.1rem; font-size:.8rem; font-weight:700; color:#fff; background:linear-gradient(135deg,#006C35,#00a050); box-shadow:0 8px 20px -10px rgba(0,108,53,.7);">
                        <i class="fa-solid fa-download"></i> {{ __('moderation.action.download') }}
                    </a>
                    @unless ($isArchive)
                        <div class="text-gray-400" style="font-size:.74rem;">{{ __('moderation.review.no_preview') }}</div>
                    @endunless
                </div>
            @endif
        </div>

        {{-- Archive contents --}}
        @if ($isArchive)
            <div class="fm-card bg-white dark:bg-gray-900">
                <div class="fm-head text-gray-950 dark:text-white">
                    <i class="fa-solid fa-folder-tree"></i> {{ __('moderation.review.archive') }}
                    @if ($archive)
                        <span class="fm-chip" style="margin-inline-start:auto; background:rgba(128,128,128,.12);">{{ __('moderation.review.archive_entries', ['count' => number_format($archive['total'])]) }} · <span dir="ltr">{{ $fmt($archive['uncompressed']) }}</span></span>
                    @endif
                </div>
                <div class="fm-body">
                    @if (! $archive && $canInspectRemote)
                        <div style="display:flex; align-items:center; gap:.9rem; flex-wrap:wrap;">
                            <button type="button" wire:click="inspectArchive" wire:loading.attr="disabled" wire:target="inspectArchive"
                                    style="display:inline-flex; align-items:center; gap:.45rem; border-radius:.7rem; padding:.55rem 1rem; font-size:.8rem; font-weight:700; color:#fff; background:linear-gradient(135deg,#006C35,#00a050);">
                                <i class="fa-solid fa-magnifying-glass" wire:loading.remove wire:target="inspectArchive"></i>
                                <i class="fa-solid fa-spinner fa-spin" wire:loading wire:target="inspectArchive"></i>
                                {{ __('moderation.review.archive_inspect') }}
                            </button>
                            <span class="text-gray-500" style="font-size:.76rem;">{{ __('moderation.review.archive_inspect_hint') }}</span>
                        </div>
                        @if ($this->remoteArchiveFailed)
                            <div style="margin-top:.6rem; font-size:.76rem; color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('moderation.review.archive_failed') }}</div>
                        @endif
                    @elseif (! $archive)
                        <div class="text-gray-500" style="font-size:.8rem;"><i class="fa-solid fa-circle-info"></i> {{ __('moderation.review.archive_unavailable') }}</div>
                    @else
                        @if ($archive['flagged'] > 0)
                            <div style="margin-bottom:.8rem; border-radius:.7rem; padding:.6rem .8rem; font-size:.78rem; font-weight:700; background:#dc26261a; color:#dc2626;">
                                <i class="fa-solid fa-triangle-exclamation"></i> {{ __('moderation.review.archive_flagged', ['count' => $archive['flagged']]) }}
                            </div>
                        @endif
                        <div style="max-height:22rem; overflow:auto;">
                            @foreach ($archive['entries'] as $e)
                                <div class="fm-row" style="{{ $e['risky'] ? 'color:#dc2626; font-weight:700;' : '' }}">
                                    <i class="fa-solid {{ $e['dir'] ? 'fa-folder text-amber-500' : ($e['risky'] ? 'fa-triangle-exclamation' : 'fa-file text-gray-400') }}" style="width:1rem; text-align:center;"></i>
                                    <span class="fm-mono" style="flex:1; min-width:0; word-break:break-all;" dir="ltr">{{ $e['name'] }}</span>
                                    @unless ($e['dir'])
                                        <span class="text-gray-400 fm-mono" dir="ltr">{{ $fmt($e['size']) }}</span>
                                    @endunless
                                </div>
                            @endforeach
                            @if ($archive['total'] > count($archive['entries']))
                                <div class="text-gray-400" style="font-size:.75rem; padding-top:.5rem;">{{ __('moderation.review.archive_more', ['count' => number_format($archive['total'] - count($archive['entries']))]) }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Details --}}
        <div class="fm-card bg-white dark:bg-gray-900">
            <div class="fm-head text-gray-950 dark:text-white"><i class="fa-solid fa-circle-info"></i> {{ __('moderation.review.details') }}</div>
            <div class="fm-body text-gray-950 dark:text-white">
                <div class="fm-facts">
                    <div style="grid-column:1 / -1;"><div class="fm-k">{{ __('moderation.review.name') }}</div><div class="fm-v">{{ $asset->original_name }}</div></div>
                    <div><div class="fm-k">{{ __('moderation.review.size') }}</div><div class="fm-v" dir="ltr">{{ $fmt($asset->size_bytes) }} <span class="text-gray-400" style="font-weight:500;">({{ number_format($asset->size_bytes) }} B)</span></div></div>
                    <div><div class="fm-k">{{ __('moderation.review.mime') }}</div><div class="fm-v fm-mono" dir="ltr">{{ $asset->mime_type ?: '—' }}</div></div>
                    <div><div class="fm-k">{{ __('moderation.review.uploaded') }}</div><div class="fm-v"><span dir="ltr">{{ $asset->created_at->format('Y-m-d H:i') }}</span> <span class="text-gray-400" style="font-weight:500;">· {{ $asset->created_at->diffForHumans() }}</span></div></div>
                    <div><div class="fm-k">{{ __('moderation.review.storage') }}</div><div class="fm-v">{{ $diskLabel }}</div></div>
                    <div><div class="fm-k">{{ __('moderation.review.downloads') }} / {{ __('moderation.review.views') }}</div><div class="fm-v" dir="ltr">{{ number_format($asset->downloads_count) }} / {{ number_format($asset->views_count) }}</div></div>
                    <div>
                        <div class="fm-k">{{ __('moderation.review.protection') }}</div>
                        <div class="fm-v">
                            @if ($asset->hasPassword())<span class="fm-chip" style="background:#d977061a; color:#d97706;"><i class="fa-solid fa-lock"></i> {{ __('moderation.review.protected') }}</span>@endif
                            @if ($asset->expires_at)<span class="fm-chip" style="background:rgba(128,128,128,.12);"><i class="fa-regular fa-clock"></i> {{ __('moderation.review.expires') }} <span dir="ltr">{{ $asset->expires_at->format('Y-m-d') }}</span></span>@endif
                            @if (! $asset->hasPassword() && ! $asset->expires_at)<span class="text-gray-400">{{ __('moderation.review.none') }}</span>@endif
                        </div>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <div class="fm-k">{{ __('moderation.review.share_link') }}</div>
                        <div class="fm-v fm-mono" dir="ltr" style="display:flex; align-items:center; gap:.5rem;">
                            <a href="{{ $asset->pageUrl() }}" target="_blank" style="color:#006C35;">{{ $asset->pageUrl() }}</a>
                            <i class="fa-regular fa-copy fm-copy" x-data x-on:click="window.fnoonCopy(@js($asset->pageUrl())); $el.classList.replace('fa-copy','fa-check')"></i>
                        </div>
                    </div>
                    @if ($asset->checksum_sha256)
                        <div style="grid-column:1 / -1;">
                            <div class="fm-k">{{ __('moderation.review.checksum') }}</div>
                            <div class="fm-v fm-mono" dir="ltr" style="display:flex; align-items:center; gap:.5rem;">
                                <span style="word-break:break-all;">{{ $asset->checksum_sha256 }}</span>
                                <i class="fa-regular fa-copy fm-copy" x-data x-on:click="window.fnoonCopy(@js($asset->checksum_sha256)); $el.classList.replace('fa-copy','fa-check')"></i>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Side column ================= --}}
    <div class="fm-col">

        {{-- Uploader --}}
        <div class="fm-card bg-white dark:bg-gray-900">
            <div class="fm-head text-gray-950 dark:text-white"><i class="fa-solid fa-user"></i> {{ __('moderation.review.owner') }}</div>
            @if ($owner)
                <div class="fm-body" style="display:flex; gap:.85rem; align-items:center;">
                    @if ($owner->avatarUrl())
                        <img src="{{ $owner->avatarUrl() }}" alt="" style="height:3.2rem; width:3.2rem; flex:0 0 auto; border-radius:9999px; object-fit:cover;">
                    @else
                        <span style="display:flex; height:3.2rem; width:3.2rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:9999px; background:linear-gradient(135deg,#006C35,#00a050); color:#fff; font-size:1.2rem; font-weight:800;">{{ mb_strtoupper(mb_substr($owner->displayName(), 0, 1)) }}</span>
                    @endif
                    <div style="min-width:0;">
                        <div class="text-gray-950 dark:text-white" style="font-weight:800; font-size:.92rem;">{{ $owner->displayName() }}</div>
                        <div class="text-gray-500" style="font-size:.75rem; word-break:break-all;" dir="ltr">{{ $owner->email }}</div>
                        <div style="display:flex; gap:.3rem; flex-wrap:wrap; margin-top:.4rem;">
                            @if ($owner->hasVerifiedEmail())
                                <span class="fm-chip" style="background:#0596691a; color:#059669;"><i class="fa-solid fa-envelope-circle-check"></i> {{ __('moderation.owner.verified') }}</span>
                            @else
                                <span class="fm-chip" style="background:#d977061a; color:#d97706;"><i class="fa-solid fa-envelope"></i> {{ __('moderation.owner.unverified') }}</span>
                            @endif
                            @if ($owner->uploads_trusted)
                                <span class="fm-chip" style="background:#0284c71a; color:#0284c7;"><i class="fa-solid fa-circle-check"></i> {{ __('moderation.owner.trusted') }}</span>
                            @endif
                            @if ($owner->isUploadBanned())
                                <span class="fm-chip" style="background:#dc26261a; color:#dc2626;"><i class="fa-solid fa-ban"></i> {{ __('moderation.owner.banned') }}</span>
                            @endif
                        </div>
                        <div class="text-gray-400" style="font-size:.7rem; margin-top:.35rem;">{{ __('moderation.owner.joined') }} <span dir="ltr">{{ $owner->created_at?->format('Y-m-d') }}</span></div>
                        @if ($owner->isUploadBanned() && $owner->uploads_ban_reason)
                            <div style="margin-top:.4rem; font-size:.74rem; color:#dc2626;">{{ $owner->uploads_ban_reason }}</div>
                        @endif
                    </div>
                </div>
                <div class="fm-stats text-gray-950 dark:text-white">
                    <div><div class="fm-num" dir="ltr">{{ number_format($ownerStats['files']) }}</div><div class="fm-lbl">{{ __('moderation.owner.files') }}</div></div>
                    <div><div class="fm-num" dir="ltr">{{ $fmt($ownerStats['bytes']) }}</div><div class="fm-lbl">{{ __('moderation.owner.storage') }}</div></div>
                    <div><div class="fm-num" dir="ltr" style="{{ $ownerStats['rejected'] ? 'color:#dc2626;' : '' }}">{{ number_format($ownerStats['rejected']) }}</div><div class="fm-lbl">{{ __('moderation.owner.rejected') }}</div></div>
                </div>
            @else
                <div class="fm-body text-gray-500" style="font-size:.8rem;">{{ __('moderation.owner.deleted') }}</div>
            @endif
        </div>

        {{-- Security scan --}}
        <div class="fm-card bg-white dark:bg-gray-900">
            <div class="fm-head text-gray-950 dark:text-white"><i class="fa-solid fa-shield-virus"></i> {{ __('moderation.review.scan') }}</div>
            <div class="fm-body">
                @if (! $asset->uploadSession)
                    <div class="text-gray-500" style="font-size:.8rem;">{{ __('moderation.review.scan_unknown') }}</div>
                @elseif (! $scan)
                    <div class="text-gray-500" style="font-size:.8rem;"><i class="fa-solid fa-spinner fa-spin"></i> {{ __('moderation.review.scan_pending') }}</div>
                @else
                    <div style="display:flex; align-items:center; gap:.7rem;">
                        <span style="display:flex; height:2.4rem; width:2.4rem; align-items:center; justify-content:center; border-radius:.75rem; font-size:1.05rem; color:{{ $scanTone }}; background:{{ $scanTone }}17;"><i class="fa-solid {{ $scanIcon }}"></i></span>
                        <div class="text-gray-950 dark:text-white" style="font-weight:800;">{{ __('monitor.scan_'.$scan) }}</div>
                    </div>
                    @if (is_array($scanStats))
                        <div style="display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.75rem;">
                            @foreach ($scanStats as $k => $v)
                                <span class="fm-chip" style="background:{{ in_array($k, ['malicious', 'suspicious']) && $v ? '#dc26261a' : 'rgba(128,128,128,.12)' }}; color:{{ in_array($k, ['malicious', 'suspicious']) && $v ? '#dc2626' : 'inherit' }};" dir="ltr">{{ $k }}: {{ is_scalar($v) ? $v : '…' }}</span>
                            @endforeach
                        </div>
                    @elseif ($scanRaw)
                        <div class="text-gray-500" style="font-size:.76rem; margin-top:.6rem;" dir="ltr">{{ \Illuminate\Support\Str::limit($scanRaw, 200) }}</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Visitor reports --}}
        <div class="fm-card bg-white dark:bg-gray-900">
            <div class="fm-head text-gray-950 dark:text-white">
                <i class="fa-solid fa-flag"></i> {{ __('moderation.review.reports') }}
                @if ($asset->reports_count)
                    <span class="fm-chip" style="margin-inline-start:auto; background:{{ $asset->open_reports ? '#dc2626' : 'rgba(128,128,128,.14)' }}; color:{{ $asset->open_reports ? '#fff' : 'inherit' }};">{{ $asset->reports_count }}</span>
                @endif
            </div>
            <div class="fm-body">
                @forelse ($reports as $rep)
                    @php
                        $repTone = ['open' => '#dc2626', 'resolved' => '#059669', 'dismissed' => '#6b7280'][$rep->status] ?? '#6b7280';
                    @endphp
                    <div style="padding:.65rem .75rem; border-radius:.8rem; margin-bottom:.6rem; background:rgba(128,128,128,.06); border:1px solid rgba(128,128,128,.12);">
                        <div style="display:flex; align-items:center; gap:.4rem; flex-wrap:wrap;">
                            <span class="fm-chip" style="background:#dc26261a; color:#dc2626;">{{ __('moderation.reason.'.$rep->reason) }}</span>
                            <span class="fm-chip" style="background:{{ $repTone }}1a; color:{{ $repTone }};">{{ __('moderation.review.report_'.$rep->status) }}</span>
                            <span class="text-gray-400" style="font-size:.68rem; margin-inline-start:auto;">{{ $rep->created_at->diffForHumans() }}</span>
                        </div>
                        @if ($rep->message)
                            <div class="text-gray-700 dark:text-gray-300" style="font-size:.78rem; margin-top:.45rem; white-space:pre-line;">{{ $rep->message }}</div>
                        @endif
                        @if ($rep->reporter_email || $rep->user)
                            <div class="text-gray-400" style="font-size:.7rem; margin-top:.35rem;" dir="ltr"><i class="fa-regular fa-envelope"></i> {{ $rep->user?->email ?? $rep->reporter_email }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-gray-500" style="font-size:.8rem;">{{ __('moderation.review.no_reports') }}</div>
                @endforelse
            </div>
        </div>

        {{-- History timeline --}}
        <div class="fm-card bg-white dark:bg-gray-900">
            <div class="fm-head text-gray-950 dark:text-white"><i class="fa-solid fa-clock-rotate-left"></i> {{ __('moderation.review.history') }}</div>
            <div class="fm-body">
                @if ($history->isEmpty())
                    <div class="text-gray-500" style="font-size:.8rem;">{{ __('moderation.review.no_history') }}</div>
                @else
                    <div class="fm-tl">
                        @foreach ($history as $h)
                            @php
                                [$hi, $hc] = $histIcon[$h->action] ?? ['fa-circle', '#6b7280'];
                            @endphp
                            <div class="fm-tl-item">
                                <span class="fm-tl-dot" style="background:{{ $hc }};"><i class="fa-solid {{ $hi }}"></i></span>
                                <div class="text-gray-950 dark:text-white" style="font-size:.8rem; font-weight:700;">
                                    {{ __('moderation.history.'.$h->action) }}
                                    @if ($h->reason)<span style="font-weight:600; color:{{ $hc }};">· {{ __('moderation.reason.'.$h->reason) }}</span>@endif
                                </div>
                                <div class="text-gray-400" style="font-size:.7rem; margin-top:.1rem;">
                                    {{ $h->actor?->name ?? __('moderation.review.system') }} · <span dir="ltr">{{ $h->created_at?->format('Y-m-d H:i') }}</span>
                                </div>
                                @if ($h->note)
                                    <div class="text-gray-600 dark:text-gray-300" style="font-size:.76rem; margin-top:.3rem; white-space:pre-line;">{{ $h->note }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
