<?php

use App\Models\Asset;
use Illuminate\Database\Migrations\Migration;

/**
 * One-off: images/PDF that already have a password, an expiry date or are
 * disabled move to the private disk (see Asset::syncVisibility) so their old
 * /storage URLs stop bypassing the protection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Asset::query()
            ->whereIn('kind', ['image', 'pdf'])
            ->where('disk', 'public')
            ->where(fn ($q) => $q->whereNotNull('password')->orWhereNotNull('expires_at')->orWhere('is_active', false))
            ->each(fn (Asset $asset) => $asset->syncVisibility());
    }

    public function down(): void
    {
        // Files move back automatically when their protection is removed.
    }
};
