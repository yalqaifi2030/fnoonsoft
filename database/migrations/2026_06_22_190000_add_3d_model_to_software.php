<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->string('model_glb')->nullable();    // .glb/.gltf web preview (powers <model-viewer>)
            $table->string('model_usdz')->nullable();   // optional .usdz for iOS AR (Quick Look)
            $table->string('model_poster')->nullable(); // optional poster image shown before load
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn(['model_glb', 'model_usdz', 'model_poster']);
        });
    }
};
