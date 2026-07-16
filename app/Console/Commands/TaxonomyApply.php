<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Software;
use App\Models\Tag;
use App\Support\Taxonomy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Re-classify the catalogue onto the real taxonomy (see App\Support\Taxonomy).
 *   php artisan taxonomy:apply --dry-run   → report only, changes nothing
 *   php artisan taxonomy:apply             → back up, then apply
 *   php artisan taxonomy:apply --rollback  → restore the previous assignments
 */
class TaxonomyApply extends Command
{
    protected $signature = 'taxonomy:apply {--dry-run : Report the planned result without changing anything}
                                           {--rollback : Restore category/tag assignments from the last backup}
                                           {--prune : Delete categories/tags that end up unused}';

    protected $description = 'Rebuild categories & tags to match the real catalogue and re-classify software';

    private const BACKUP = 'taxonomy-backup.json';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dry = $this->option('dry-run');
        $software = Software::with('tags')->get();

        // Plan every item first (no writes yet).
        $plan = [];
        foreach ($software as $s) {
            $plan[$s->id] = Taxonomy::classify($s);
        }

        // ---- Report -----------------------------------------------------
        $byCat = [];
        foreach ($plan as $id => $p) {
            $byCat[$p['category']][] = $software->firstWhere('id', $id);
        }

        $this->newLine();
        $this->info('== PLANNED CATEGORIES ==');
        $rows = [];
        foreach (Taxonomy::CATEGORIES as $slug => [$ar, $en]) {
            $items = $byCat[$slug] ?? [];
            $sample = collect($items)->take(3)
                ->map(fn ($s) => mb_substr(preg_replace('/[^\x20-\x7E]/', '', (string) $s->name) ?: (string) $s->name, 0, 28))
                ->implode(' · ');
            $rows[] = [$ar, count($items), $sample];
        }
        $this->table(['الفئة', 'عدد', 'أمثلة'], $rows);

        $tagCount = [];
        foreach ($plan as $p) {
            foreach ($p['tags'] as $t) {
                $tagCount[$t] = ($tagCount[$t] ?? 0) + 1;
            }
        }
        arsort($tagCount);
        $this->info('== PLANNED TAGS ==');
        $this->table(['الوسم', 'عدد'], collect($tagCount)
            ->map(fn ($c, $slug) => [Taxonomy::TAGS[$slug][0] ?? $slug, $c])->values()->all());

        $uncategorised = count($byCat['utilities'] ?? []);
        $this->line("total software: {$software->count()} · falling back to 'utilities': {$uncategorised}");

        if ($dry) {
            $this->newLine();
            $this->warn('DRY RUN — nothing was changed.');

            return self::SUCCESS;
        }

        // ---- Backup -----------------------------------------------------
        $backup = $software->mapWithKeys(fn ($s) => [$s->id => [
            'category_id' => $s->category_id,
            'tags' => $s->tags->pluck('id')->all(),
        ]])->all();
        Storage::disk('local')->put(self::BACKUP, json_encode($backup, JSON_PRETTY_PRINT));
        $this->info('backup written: storage/app/'.self::BACKUP);

        // ---- Ensure categories & tags exist ------------------------------
        $catIds = [];
        $sort = 0;
        foreach (Taxonomy::CATEGORIES as $slug => [$ar, $en, $icon]) {
            $cat = Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => ['ar' => $ar, 'en' => $en], 'icon' => $icon, 'is_active' => true, 'sort_order' => $sort++, 'parent_id' => null],
            );
            $catIds[$slug] = $cat->id;
        }

        $tagIds = [];
        foreach (Taxonomy::TAGS as $slug => [$ar, $en]) {
            $tag = Tag::updateOrCreate(['slug' => $slug], ['name' => ['ar' => $ar, 'en' => $en]]);
            $tagIds[$slug] = $tag->id;
        }

        // ---- Apply -------------------------------------------------------
        $bar = $this->output->createProgressBar($software->count());
        foreach ($software as $s) {
            $p = $plan[$s->id];
            $s->forceFill(['category_id' => $catIds[$p['category']]])->saveQuietly();
            $s->tags()->sync(array_map(fn ($t) => $tagIds[$t], $p['tags']));
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info('re-classified '.$software->count().' item(s).');

        // ---- Prune leftovers --------------------------------------------
        if ($this->option('prune')) {
            $deadCats = Category::whereNotIn('slug', array_keys(Taxonomy::CATEGORIES))
                ->whereDoesntHave('software')->get();
            foreach ($deadCats as $c) {
                $this->line('  removed category: '.$c->name);
                $c->delete();
            }
            $deadTags = Tag::whereNotIn('slug', array_keys(Taxonomy::TAGS))
                ->whereDoesntHave('software')->get();
            foreach ($deadTags as $t) {
                $this->line('  removed tag: '.$t->name);
                $t->delete();
            }
        }

        $this->newLine();
        $this->info('Done. Roll back any time with: php artisan taxonomy:apply --rollback');

        return self::SUCCESS;
    }

    private function rollback(): int
    {
        if (! Storage::disk('local')->exists(self::BACKUP)) {
            $this->error('No backup found at storage/app/'.self::BACKUP);

            return self::FAILURE;
        }

        $backup = json_decode(Storage::disk('local')->get(self::BACKUP), true);
        $n = 0;
        foreach ($backup as $id => $row) {
            $s = Software::find($id);
            if (! $s) {
                continue;
            }
            $s->forceFill(['category_id' => $row['category_id']])->saveQuietly();
            $s->tags()->sync($row['tags']);
            $n++;
        }

        $this->info("restored {$n} item(s) to their previous categories/tags.");

        return self::SUCCESS;
    }
}
