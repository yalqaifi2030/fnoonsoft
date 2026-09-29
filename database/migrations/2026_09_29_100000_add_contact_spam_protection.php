<?php

use App\Models\Contact;
use App\Support\SpamGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact-form spam protection: every message is scored (links, ad phrases,
 * bot-style names/languages, repeat senders) and suspicious ones land in a
 * Spam folder; blocked senders (email / domain / IP) are dropped silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_spam')->default(false)->after('is_read');
            $table->unsignedSmallInteger('spam_score')->default(0)->after('is_spam');
            $table->json('spam_reasons')->nullable()->after('spam_score');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
            $table->string('country', 2)->nullable()->after('user_agent');
            $table->index(['is_spam', 'created_at']);
        });

        Schema::create('contact_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // email | domain | ip
            $table->string('value', 190);
            $table->string('reason', 255)->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['type', 'value']);
        });

        // Score the messages already received.
        Contact::query()->each(function (Contact $c) {
            [$score, $reasons] = SpamGuard::score($c->only(['name', 'email', 'subject', 'message']), $c->ip_address, $c->id);
            $c->forceFill([
                'spam_score' => $score,
                'spam_reasons' => $reasons ?: null,
                'is_spam' => $score >= SpamGuard::THRESHOLD,
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_blocks');
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['is_spam', 'created_at']);
            $table->dropColumn(['is_spam', 'spam_score', 'spam_reasons', 'user_agent', 'country']);
        });
    }
};
