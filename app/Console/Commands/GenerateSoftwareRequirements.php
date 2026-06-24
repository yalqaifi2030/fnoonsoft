<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\Software;
use App\Models\SystemRequirement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fill realistic minimum + recommended system requirements for software via the
 * Claude API (Messages endpoint, structured tool use). Powers the compatibility
 * checker. One-off backfill — run for existing software that has no requirements.
 */
class GenerateSoftwareRequirements extends Command
{
    protected $signature = 'requirements:generate
        {--id=* : Only these software id(s) (overwrites their requirements)}
        {--limit=0 : Max number of items to process}
        {--all : Include items that already have requirements}';

    protected $description = 'Generate realistic system requirements for software using the Claude API';

    public function handle(): int
    {
        $key = config('services.anthropic.key');
        if (! $key) {
            $this->error('ANTHROPIC_API_KEY is not set in .env');

            return self::FAILURE;
        }
        $model = config('services.anthropic.model');

        $q = Software::query()->where('status', ContentStatus::Published->value);
        if ($ids = array_filter((array) $this->option('id'))) {
            $q->whereIn('id', $ids);
        } elseif (! $this->option('all')) {
            $q->whereDoesntHave('requirements');
        }
        if (($limit = (int) $this->option('limit')) > 0) {
            $q->limit($limit);
        }

        $items = $q->get(['id', 'name', 'os_support']);
        $this->info("Processing {$items->count()} software with model {$model}…");
        $ok = 0;
        $fail = 0;

        foreach ($items as $s) {
            $os = $this->primaryOs($s->os_support);
            try {
                $reqs = $this->ask($key, $model, (string) $s->name, $os);
                if (! $reqs || empty($reqs['minimum'])) {
                    $fail++;
                    $this->warn("  ✗ #{$s->id} no data returned");

                    continue;
                }

                SystemRequirement::where('software_id', $s->id)->delete();
                foreach (['minimum', 'recommended'] as $tier) {
                    $r = $reqs[$tier] ?? null;
                    if (! is_array($r)) {
                        continue;
                    }
                    SystemRequirement::create([
                        'software_id' => $s->id,
                        'os' => $os,
                        'tier' => $tier,
                        'processor' => $r['processor'] ?? null,
                        'memory' => $r['memory'] ?? null,
                        'storage' => $r['storage'] ?? null,
                        'graphics' => $r['graphics'] ?? null,
                        'os_version' => $r['os_version'] ?? null,
                    ]);
                }
                $ok++;
                $this->line("  ✓ #{$s->id} ".mb_substr((string) $s->name, 0, 42)
                    .'  → '.($reqs['minimum']['memory'] ?? '?').' / '.($reqs['recommended']['memory'] ?? '?'));
            } catch (\Throwable $e) {
                $fail++;
                $this->warn("  ✗ #{$s->id} ".$e->getMessage());
                Log::warning('[fnoon] requirements:generate failed', ['id' => $s->id, 'msg' => $e->getMessage()]);
            }

            usleep(1_300_000); // stay well under tier-1 rate limits (~50 rpm)
        }

        $this->info("Done. ok={$ok} fail={$fail}");

        return self::SUCCESS;
    }

    private function primaryOs(mixed $osSupport): string
    {
        $valid = ['windows', 'macos', 'linux', 'android', 'ios'];
        foreach ((array) $osSupport as $o) {
            $o = strtolower(trim((string) $o));
            if (in_array($o, $valid, true)) {
                return $o;
            }
        }

        return 'windows';
    }

    private function ask(string $key, string $model, string $name, string $os): ?array
    {
        $osLabel = ['windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux', 'android' => 'Android', 'ios' => 'iOS'][$os] ?? 'Windows';
        $prompt = "Provide realistic MINIMUM and RECOMMENDED system requirements for this software on {$osLabel}.\n"
            ."Software: \"{$name}\".\n"
            .'Use the official/published requirements where you know them; otherwise give a sensible estimate from the software category. '
            .'Memory must be written like "8 GB RAM". Call the save_requirements tool with both tiers filled.';

        $resp = null;
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $resp = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'tools' => [$this->tool()],
                'tool_choice' => ['type' => 'tool', 'name' => 'save_requirements'],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($resp->status() === 429 || $resp->status() >= 500) {
                $wait = (int) ($resp->header('retry-after') ?: 6);
                sleep(min(max($wait, 3), 30));

                continue;
            }
            break;
        }

        if (! $resp || $resp->failed()) {
            throw new \RuntimeException('API '.($resp?->status() ?? '?').': '.mb_substr((string) $resp?->body(), 0, 180));
        }

        foreach ((array) $resp->json('content', []) as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                return $block['input'] ?? null;
            }
        }

        return null;
    }

    private function tool(): array
    {
        $spec = [
            'type' => 'object',
            'properties' => [
                'processor' => ['type' => 'string', 'description' => 'CPU, e.g. "Intel Core i5 / AMD Ryzen 5 (64-bit)"'],
                'memory' => ['type' => 'string', 'description' => 'RAM — MUST include a number and GB, e.g. "8 GB RAM"'],
                'storage' => ['type' => 'string', 'description' => 'Free disk space, e.g. "4 GB"'],
                'graphics' => ['type' => 'string', 'description' => 'GPU, e.g. "DirectX 12 GPU with 2 GB VRAM"'],
                'os_version' => ['type' => 'string', 'description' => 'OS version, e.g. "Windows 10/11 (64-bit)"'],
            ],
            'required' => ['processor', 'memory', 'storage', 'graphics', 'os_version'],
        ];

        return [
            'name' => 'save_requirements',
            'description' => 'Save the minimum and recommended system requirements for the software.',
            'input_schema' => [
                'type' => 'object',
                'properties' => ['minimum' => $spec, 'recommended' => $spec],
                'required' => ['minimum', 'recommended'],
            ],
        ];
    }
}
