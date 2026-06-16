<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Software;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The "المساعد الذكي" (AI Assistant) engine. It chats with public visitors in
 * natural Arabic/English and recommends software ONLY from the live catalog —
 * never inventing products that don't exist. Backed by the Claude Messages API.
 *
 * Config can come from saved settings (public widget) or be passed explicitly
 * (admin live-preview, so the admin can test before publishing).
 */
class AssistantService
{
    public const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    /** Models offered in the admin dropdown (id => label key). */
    public const MODELS = [
        'claude-haiku-4-5' => 'Claude Haiku 4.5',
        'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
        'claude-opus-4-8' => 'Claude Opus 4.8',
    ];

    public function __construct(
        protected string $apiKey = '',
        protected string $model = 'claude-haiku-4-5',
        protected string $persona = '',
        protected int $maxRecommendations = 6,
    ) {}

    /** Build from saved admin settings (used by the public widget). */
    public static function fromSettings(): self
    {
        return new self(
            apiKey: (string) Setting::get('assistant_api_key', ''),
            model: (string) Setting::get('assistant_model', 'claude-haiku-4-5'),
            persona: (string) Setting::get('assistant_persona', ''),
            maxRecommendations: (int) (Setting::get('assistant_max_recs', 6) ?: 6),
        );
    }

    /** Build from explicit (possibly unsaved) values — admin live preview. */
    public static function fromConfig(array $c): self
    {
        return new self(
            apiKey: (string) ($c['api_key'] ?? ''),
            model: (string) ($c['model'] ?? 'claude-haiku-4-5'),
            persona: (string) ($c['persona'] ?? ''),
            maxRecommendations: (int) ($c['max_recs'] ?? 6),
        );
    }

    public static function isEnabled(): bool
    {
        return (bool) Setting::get('assistant_enabled', false);
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /** Is the public widget ready to show? (enabled + has a key) */
    public static function isLive(): bool
    {
        return self::isEnabled() && filled((string) Setting::get('assistant_api_key', ''));
    }

    /**
     * Send the conversation to Claude and return the reply plus any catalog
     * items it recommended.
     *
     * @param  array<int,array{role:string,content:string}>  $history
     * @return array{reply:string,recommendations:array<int,array>,error?:string}
     */
    public function reply(array $history): array
    {
        if (! $this->isConfigured()) {
            return ['reply' => '', 'recommendations' => [], 'error' => 'not_configured'];
        }

        $catalog = $this->catalog();

        // Keep token use bounded: only the most recent turns, sanitised.
        $messages = collect($history)
            ->filter(fn ($m) => in_array(($m['role'] ?? ''), ['user', 'assistant'], true) && filled($m['content'] ?? ''))
            ->map(fn ($m) => [
                'role' => $m['role'],
                'content' => Str::limit((string) $m['content'], 4000, ''),
            ])
            ->slice(-12)
            ->values()
            ->all();

        if (empty($messages) || $messages[0]['role'] !== 'user') {
            return ['reply' => '', 'recommendations' => [], 'error' => 'bad_request'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post(self::ENDPOINT, [
                'model' => $this->model ?: 'claude-haiku-4-5',
                'max_tokens' => 1024,
                'system' => $this->systemPrompt($catalog['text']),
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            return ['reply' => '', 'recommendations' => [], 'error' => 'network'];
        }

        if ($response->failed()) {
            $type = $response->json('error.type', 'api');

            return ['reply' => '', 'recommendations' => [], 'error' => $type];
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        return $this->parse($text, $catalog['items']);
    }

    /** Extract [[slug]] markers into recommendation cards; strip them from the prose. */
    protected function parse(string $text, array $items): array
    {
        preg_match_all('/\[\[([a-z0-9\-]+)\]\]/i', $text, $m);

        $recommendations = [];
        foreach (array_unique($m[1] ?? []) as $slug) {
            if (isset($items[$slug]) && count($recommendations) < $this->maxRecommendations) {
                $recommendations[] = $items[$slug];
            }
        }

        $clean = trim(preg_replace('/\s*\[\[[a-z0-9\-]+\]\]/i', '', $text));

        return ['reply' => $clean, 'recommendations' => $recommendations];
    }

    /** The instruction prompt — persona + the hard "catalog only" guardrail. */
    protected function systemPrompt(string $catalogText): string
    {
        $persona = trim($this->persona) ?: $this->defaultPersona();
        $site = Setting::text('site_name', config('app.name'));

        return <<<PROMPT
{$persona}

أنت مساعد ذكي لمتجر «{$site}» لتحميل البرامج والسكربتات والقوالب والإضافات.

قواعد صارمة يجب الالتزام بها:
- ساعد الزائر على إيجاد ما يناسبه، واقترح عليه عناصر مناسبة من الكتالوج أدناه فقط.
- ممنوع منعًا باتًّا اختراع أو ذكر أي برنامج أو منتج غير موجود في الكتالوج. لا تخمّن أسماء.
- إذا لم تجد ما يناسب طلب الزائر في الكتالوج، أخبره بصراحة واقترح عليه تصفّح الموقع أو إعادة صياغة طلبه.
- عند التوصية بعنصر، اذكر اسمه بشكل طبيعي ثم ضع مباشرةً بعده العلامة [[slug]] باستخدام المُعرّف (slug) المذكور بين قوسين في الكتالوج، لكي يعرض النظام بطاقة المنتج. مثال: «أنصحك بـ Adobe Photoshop [[adobe-photoshop]]».
- لا تكتب أي روابط بنفسك؛ النظام يضيف بطاقات المنتجات تلقائيًّا من العلامات.
- ردّ بنفس لغة الزائر (العربية افتراضيًّا)، بإيجاز وودّ واحترافية. لا تَطُل.
- لا تتحدّث عن الأسعار أو تفاصيل غير مؤكدة؛ ركّز على مساعدة الزائر في الاختيار.

الكتالوج المتاح (لا تقترح خارجه):
{$catalogText}
PROMPT;
    }

    protected function defaultPersona(): string
    {
        return 'أنت «نون»، مساعد ودود وخبير يساعد الزوّار في اختيار البرامج والقوالب والسكربتات المناسبة لاحتياجاتهم.';
    }

    /**
     * Compact, cached snapshot of the published catalog: a prompt-ready text
     * block plus a slug => card-meta map for rendering recommendations.
     *
     * @return array{text:string,items:array<string,array>}
     */
    public function catalog(): array
    {
        return Cache::remember('assistant.catalog', 600, function () {
            $rows = Software::query()
                ->published()
                ->with('category')
                ->orderByDesc('downloads_count')
                ->limit(400)
                ->get();

            $lines = [];
            $items = [];

            foreach ($rows as $s) {
                $slug = (string) $s->slug;
                if ($slug === '') {
                    continue;
                }

                $name = $this->pick($s, 'name') ?: $slug;
                $desc = Str::limit(strip_tags((string) $this->pick($s, 'short_description')), 140, '');
                $type = $s->content_type?->label() ?? '';
                $category = $s->category?->name ?? '';

                $meta = trim(collect([$type, $category])->filter()->implode(' · '));
                $lines[] = trim("- [{$slug}] {$name}".($meta ? " ({$meta})" : '').($desc ? " — {$desc}" : ''));

                $items[$slug] = [
                    'slug' => $slug,
                    'name' => $name,
                    'type' => $type,
                    'icon' => $s->icon ? Storage::disk('public')->url($s->icon) : null,
                    'url' => route('software.show', $slug),
                ];
            }

            return [
                'text' => $lines ? implode("\n", $lines) : '(لا توجد عناصر منشورة بعد)',
                'items' => $items,
            ];
        });
    }

    /** Prefer the Arabic translation, fall back to English, then the raw value. */
    protected function pick(Software $s, string $attr): string
    {
        try {
            return (string) ($s->getTranslation($attr, 'ar', false)
                ?: $s->getTranslation($attr, 'en', false)
                ?: $s->{$attr});
        } catch (\Throwable $e) {
            return (string) $s->{$attr};
        }
    }

    public static function forgetCatalog(): void
    {
        Cache::forget('assistant.catalog');
    }
}
