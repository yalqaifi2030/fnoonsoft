{{-- Renders the email's full HTML in an isolated iframe so its styles don't
     leak into the admin panel. --}}
<div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10">
    <iframe srcdoc="{{ $html }}" title="email preview"
            style="width:100%; height:72vh; border:0; background:#f1f1f4;"></iframe>
</div>
