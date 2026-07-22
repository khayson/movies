<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tmdb_id');
            $table->string('media_type', 10);
            $table->string('title');
            $table->string('poster_path')->nullable();
            $table->text('overview')->nullable();
            $table->string('release_date')->nullable();
            $table->decimal('vote_average', 3, 1)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'tmdb_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlists');
    }
};
