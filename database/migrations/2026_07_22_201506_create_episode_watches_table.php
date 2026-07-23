<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('episode_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tmdb_id');
            $table->unsignedSmallInteger('season_number');
            $table->unsignedSmallInteger('episode_number');
            $table->timestamp('watched_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'tmdb_id', 'season_number', 'episode_number']);
            $table->index(['user_id', 'tmdb_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_watches');
    }
};
