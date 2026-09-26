@php
    $asset = $getRecord();
    $ext = strtolower(pathinfo((string) $asset->original_name, PATHINFO_EXTENSION));
    [$icon, $hex] = match (true) {
        $asset->isPdf() => ['fa-file-pdf', '#dc2626'],
        in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz']) => ['fa-file-zipper', '#d97706'],
        in_array($ext, ['exe', 'msi', 'dmg', 'pkg', 'deb', 'rpm', 'appimage']) => ['fa-window-maximize', '#0284c7'],
        in_array($ext, ['apk', 'aab', 'ipa']) => ['fa-mobile-screen', '#16a34a'],
        in_array($ext, ['iso', 'img', 'bin']) => ['fa-compact-disc', '#4f46e5'],
        in_array($ext, ['php', 'js', 'ts', 'py', 'rb', 'go', 'rs', 'java', 'jar', 'sql', 'json', 'xml', 'yml', 'yaml', 'env']) => ['fa-file-code', '#7c3aed'],
        in_array($ext, ['obj', 'fbx', 'gltf', 'glb', 'stl', 'blend', 'max', 'c4d', '3dm', 'skp', '3ds', 'dae', 'usdz']) => ['fa-cube', '#0d9488'],
        default => ['fa-file', '#6b7280'],
    };
    $thumb = $asset->isImage() ? $asset->thumbUrl() : null;
    $flag = $asset->open_reports > 0;
@endphp
<div style="position:relative; padding-inline-start:.75rem; padding-block:.35rem;">
    @if ($thumb)
        <img src="{{ $thumb }}" alt="" loading="lazy"
             style="height:2.9rem; width:2.9rem; border-radius:.7rem; object-fit:cover; border:1px solid rgba(128,128,128,.18); background:#f3f4f6;">
    @else
        <span style="display:flex; height:2.9rem; width:2.9rem; align-items:center; justify-content:center; border-radius:.7rem; font-size:1.2rem; color:{{ $hex }}; background:{{ $hex }}14; border:1px solid {{ $hex }}2e;">
            <i class="fa-solid {{ $icon }}"></i>
        </span>
    @endif
    @if ($flag)
        <span title="{{ __('moderation.tab.reported') }}"
              style="position:absolute; top:0; inset-inline-end:-.3rem; display:flex; height:1.15rem; width:1.15rem; align-items:center; justify-content:center; border-radius:9999px; background:#dc2626; color:#fff; font-size:.55rem; box-shadow:0 0 0 2px #fff;">
            <i class="fa-solid fa-flag"></i>
        </span>
    @endif
</div>
