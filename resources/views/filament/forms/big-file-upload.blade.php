{{-- In-form big-file uploader for Software. Same resumable multipart engine as
     the upload panel; on completion it dispatches `bigFileUploaded` which the page
     turns into an `r2` download link (see App\Filament\Concerns\HandlesBigFileUpload). --}}
<div
    x-data="fnoonAdminUploader({
        target: 'fnoon-admin-uploader',
        maxBytes: {{ (int) env('UPLOAD_MAX_BYTES', 32212254720) }},
        partSize: {{ (int) env('UPLOAD_PART_SIZE', 33554432) }},
        createUrl: @js(route('upload.multipart.create')),
        signUrl: @js(route('upload.multipart.sign')),
        completeUrl: @js(route('upload.multipart.complete')),
        abortUrl: @js(route('upload.multipart.abort')),
        locale: @js(app()->getLocale() === 'ar' ? [
            'strings' => [
                'dropPasteFiles' => 'أفلت الملفات هنا أو %{browseFiles}',
                'browseFiles' => 'تصفّح الملفات',
                'uploading' => 'جارٍ الرفع', 'complete' => 'مكتمل',
                'uploadComplete' => 'اكتمل الرفع', 'retryUpload' => 'إعادة المحاولة',
                'xFilesSelected' => ['0' => 'تم اختيار %{smart_count} ملف', '1' => 'تم اختيار %{smart_count} ملفات'],
                'uploadXFiles' => ['0' => 'رفع %{smart_count} ملف', '1' => 'رفع %{smart_count} ملفات'],
            ],
        ] : null),
    })"
    wire:ignore
>
    <div class="rounded-xl border border-primary-200 bg-primary-50/40 dark:border-primary-500/20 dark:bg-primary-500/5 p-3">
        <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-primary-700 dark:text-primary-300">
            <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="h-5 w-5" />
            {{ __('software.uploader.title') }}
            <span class="ms-auto rounded-full bg-primary-600 px-2 py-0.5 text-[11px] font-bold text-white" dir="ltr">
                {{ number_format((int) env('UPLOAD_MAX_BYTES', 32212254720) / 1073741824, 0) }} GB
            </span>
        </div>
        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('software.uploader.hint') }}</p>
        <div id="fnoon-admin-uploader"></div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Load Uppy once (CDN), then announce readiness.
            if (! window.__fnoonUppyLoading) {
                window.__fnoonUppyLoading = true;
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://releases.transloadit.com/uppy/v3.27.0/uppy.min.css';
                document.head.appendChild(css);
                const js = document.createElement('script');
                js.src = 'https://releases.transloadit.com/uppy/v3.27.0/uppy.min.js';
                js.onload = () => { window.__fnoonUppyReady = true; window.dispatchEvent(new Event('fnoon-uppy-ready')); };
                document.head.appendChild(js);
            }

            function fnoonAdminUploader(opts) {
                return {
                    init() {
                        const mount = () => {
                            const el = document.getElementById(opts.target);
                            if (! el || el.dataset.mounted) return;   // guard against double-mount
                            el.dataset.mounted = '1';

                            const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            const post = async (url, body) => {
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                                    body: JSON.stringify(body),
                                });
                                if (! res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.message || ('Upload error ' + res.status)); }
                                return res.json();
                            };

                            const coreOpts = { autoProceed: false, restrictions: { maxNumberOfFiles: 10, maxFileSize: opts.maxBytes } };
                            if (opts.locale) coreOpts.locale = opts.locale;

                            new Uppy.Uppy(coreOpts)
                                .use(Uppy.Dashboard, { inline: true, target: '#' + opts.target, height: 300, proudlyDisplayPoweredByUppy: false })
                                .use(Uppy.AwsS3Multipart, {
                                    limit: 4,
                                    retryDelays: [0, 3000, 6000, 12000, 24000, 30000],
                                    getChunkSize: () => opts.partSize,
                                    createMultipartUpload: async (file) => {
                                        const d = await post(opts.createUrl, { filename: file.name, type: file.type, size: file.size });
                                        file.meta.sessionUuid = d.sessionUuid; file.meta.r2key = d.key;
                                        return { uploadId: d.uploadId, key: d.key };
                                    },
                                    signPart: async (file, { uploadId, key, partNumber }) => {
                                        const d = await post(opts.signUrl, { key, uploadId, partNumber });
                                        return { url: d.url };
                                    },
                                    completeMultipartUpload: async (file, { uploadId, key, parts }) => {
                                        const d = await post(opts.completeUrl, { sessionUuid: file.meta.sessionUuid, key, uploadId, parts });
                                        return { location: d.location };
                                    },
                                    abortMultipartUpload: async (file, { uploadId, key }) => {
                                        await post(opts.abortUrl, { sessionUuid: file.meta.sessionUuid, key, uploadId });
                                    },
                                })
                                .on('upload-error', (file, error, response) => {
                                    console.error('[fnoon upload] part failed:', error?.message || error, 'status:', response?.status, response);
                                })
                                .on('complete', (result) => {
                                    (result.successful || []).forEach((file) => {
                                        Livewire.dispatch('bigFileUploaded', {
                                            key: file.meta.r2key,
                                            size: file.size || null,
                                            name: file.name,
                                        });
                                    });
                                });
                        };

                        window.__fnoonUppyReady ? mount() : window.addEventListener('fnoon-uppy-ready', mount, { once: true });
                    },
                };
            }
        </script>
    @endpush
@endonce
