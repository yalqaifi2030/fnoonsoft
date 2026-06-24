<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\Software;
use App\Models\SystemRequirement;
use Illuminate\Console\Command;

/**
 * FREE backfill of system requirements (no API). OS is taken from the software's
 * own os_support; RAM/CPU/GPU/storage are sensible category-tiered estimates
 * (heavy engineering/3D/video → 16/32 GB, design → 8/16 GB, light tools → 4/8 GB).
 * Admins can refine any item afterwards. Powers the compatibility checker.
 */
class FillDefaultRequirements extends Command
{
    protected $signature = 'requirements:defaults
        {--id=* : Only these software id(s)}
        {--limit=0 : Max number of items}
        {--all : Include items that already have requirements}
        {--overwrite : Replace existing requirements}';

    protected $description = 'Fill baseline system requirements from category heuristics (free, no API)';

    /** keyword → tier. Checked against the software name + its category name. */
    private const HEAVY = [
        'revit', 'autocad', 'auto cad', 'civil 3d', 'navisworks', 'lumion', 'twinmotion', 'etabs', 'sap2000',
        'safe', 'csi', 'robot', 'staad', 'tekla', 'archicad', 'sketchup', '3ds max', '3dsmax', 'maya', 'blender',
        'cinema 4d', 'c4d', 'solidworks', 'inventor', 'fusion', 'rhino', 'keyshot', 'vray', 'v-ray', 'unreal',
        'unity', 'matlab', 'ansys', 'abaqus', 'premiere', 'after effects', 'davinci', 'resolve', 'vegas', 'edius',
        'nuke', 'houdini', 'zbrush', 'substance', 'render', 'modeling', 'simulation', 'bim', 'cad',
        'ريفيت', 'أوتوكاد', 'اوتوكاد', 'نافيس', 'لوميون', 'إيتابس', 'ايتابس', 'محاكاة', 'هندسي', 'هندسة', 'إنشائي',
        'انشائي', 'ثلاثي الأبعاد', 'ثلاثي الابعاد', 'نمذجة', 'رندر', 'مونتاج', 'فيديو', 'معماري', 'تصميم',
    ];

    private const LIGHT = [
        'pdf', 'reader', 'viewer', 'plugin', 'add-in', 'addin', 'extension', 'script', 'utility', 'font',
        'converter', 'compress', 'codec', 'driver',
        'قارئ', 'عارض', 'إضافة', 'اضافة', 'ملحق', 'اسكربت', 'سكربت', 'أداة', 'اداة', 'خط', 'خطوط', 'محول',
        'تحويل', 'ضغط',
    ];

    public function handle(): int
    {
        $q = Software::query()->with('category:id,name')->where('status', ContentStatus::Published->value);
        if ($ids = array_filter((array) $this->option('id'))) {
            $q->whereIn('id', $ids);
        } elseif (! $this->option('all') && ! $this->option('overwrite')) {
            $q->whereDoesntHave('requirements');
        }
        if (($limit = (int) $this->option('limit')) > 0) {
            $q->limit($limit);
        }

        $items = $q->get();
        $this->info("Filling {$items->count()} software…");
        $count = 0;

        foreach ($items as $s) {
            $os = $this->primaryOs($s->os_support);
            $tier = $this->classify(mb_strtolower((string) $s->name).' '.mb_strtolower((string) optional($s->category)->name));
            $specs = $this->specs($tier, $os);

            if ($this->option('overwrite') || $ids) {
                SystemRequirement::where('software_id', $s->id)->delete();
            }
            foreach (['minimum', 'recommended'] as $level) {
                SystemRequirement::create(array_merge(
                    ['software_id' => $s->id, 'os' => $os, 'tier' => $level],
                    $specs[$level],
                ));
            }
            $count++;
            $this->line("  ✓ #{$s->id} [{$tier}/{$os}] ".mb_substr((string) $s->name, 0, 42));
        }

        $this->info("Done. filled {$count} software.");

        return self::SUCCESS;
    }

    private function classify(string $haystack): string
    {
        // Uniform high baseline for every program by default (i7/16GB → i9/32GB);
        // admins refine individual items afterwards. (Category tiers kept below in
        // case per-category defaults are wanted again.)
        return 'heavy';
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

    private function osVersion(string $os): string
    {
        return [
            'windows' => 'Windows 10/11 (64-bit)',
            'macos' => 'macOS 12 (Monterey) or later',
            'linux' => '64-bit Linux (Ubuntu 20.04+)',
            'android' => 'Android 9.0 or later',
            'ios' => 'iOS 14 or later',
        ][$os] ?? 'Windows 10/11 (64-bit)';
    }

    /** @return array{minimum: array, recommended: array} */
    private function specs(string $tier, string $os): array
    {
        $osv = $this->osVersion($os);
        $mobile = in_array($os, ['android', 'ios'], true);

        $table = [
            'light' => [
                'min' => ['cpu' => 'Intel Core i3 / AMD (64-bit)', 'ram' => '4 GB RAM', 'disk' => '1 GB', 'gpu' => 'Integrated graphics (DirectX 10)'],
                'rec' => ['cpu' => 'Intel Core i5 / AMD Ryzen 5', 'ram' => '8 GB RAM', 'disk' => '2 GB', 'gpu' => 'Integrated graphics (DirectX 11)'],
            ],
            'standard' => [
                'min' => ['cpu' => 'Intel Core i5 / AMD Ryzen 5 (64-bit)', 'ram' => '8 GB RAM', 'disk' => '4 GB', 'gpu' => 'GPU with 2 GB VRAM (DirectX 12)'],
                'rec' => ['cpu' => 'Intel Core i7 / AMD Ryzen 7', 'ram' => '16 GB RAM', 'disk' => '8 GB SSD', 'gpu' => 'GPU with 4 GB VRAM'],
            ],
            'heavy' => [
                'min' => ['cpu' => 'Intel Core i7 / AMD Ryzen 7 (64-bit)', 'ram' => '16 GB RAM', 'disk' => '10 GB', 'gpu' => 'GPU with 4 GB VRAM (DirectX 12)'],
                'rec' => ['cpu' => 'Intel Core i9 / AMD Ryzen 9', 'ram' => '32 GB RAM', 'disk' => '20 GB SSD', 'gpu' => 'NVIDIA RTX / 8 GB VRAM'],
            ],
        ][$tier];

        $apple = $os === 'macos';
        $build = function (array $row, string $level) use ($osv, $apple, $mobile): array {
            $cpu = $apple
                ? ($level === 'min' ? 'Apple Silicon (M1) or Intel Core i5' : 'Apple Silicon (M2+) or Intel Core i7')
                : ($mobile ? ($level === 'min' ? 'Octa-core 2.0 GHz' : 'Recent flagship chipset') : $row['cpu']);

            return [
                'processor' => $cpu,
                'memory' => $row['ram'],
                'storage' => $row['disk'],
                'graphics' => $mobile ? 'Adreno / Mali / Apple GPU' : $row['gpu'],
                'os_version' => $osv,
            ];
        };

        return [
            'minimum' => $build($table['min'], 'min'),
            'recommended' => $build($table['rec'], 'rec'),
        ];
    }
}
