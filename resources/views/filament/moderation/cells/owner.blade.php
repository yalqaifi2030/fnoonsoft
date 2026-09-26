@php
    $u = $getRecord()->user;
    $avatar = $u?->avatarUrl();
    $name = $u?->displayName() ?? __('moderation.owner.deleted');
@endphp
<div style="display:flex; align-items:center; gap:.6rem; padding-block:.35rem; min-width:11rem;">
    @if ($avatar)
        <img src="{{ $avatar }}" alt="" style="height:2rem; width:2rem; flex:0 0 auto; border-radius:9999px; object-fit:cover;">
    @else
        <span style="display:flex; height:2rem; width:2rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:9999px; background:linear-gradient(135deg,#006C35,#00a050); color:#fff; font-size:.8rem; font-weight:800;">
            {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
        </span>
    @endif
    <div style="min-width:0; line-height:1.25;">
        <div style="display:flex; align-items:center; gap:.3rem; flex-wrap:wrap;">
            <span class="text-gray-950 dark:text-white" style="font-size:.82rem; font-weight:700;">{{ \Illuminate\Support\Str::limit($name, 24) }}</span>
            @if ($u?->uploads_trusted)
                <span title="{{ __('moderation.owner.trusted') }}" style="color:#0284c7; font-size:.75rem;"><i class="fa-solid fa-circle-check"></i></span>
            @endif
            @if ($u?->isUploadBanned())
                <span style="border-radius:9999px; padding:.05rem .45rem; font-size:.62rem; font-weight:700; background:#dc26261a; color:#dc2626;">{{ __('moderation.owner.banned') }}</span>
            @endif
        </div>
        @if ($u)
            <div class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;" dir="ltr">{{ \Illuminate\Support\Str::limit($u->email, 30) }}</div>
        @endif
    </div>
</div>
