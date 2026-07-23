<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tmdb_id');
            $table->string('media_type', 10);
            $table->string('title');
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->boolean('contains_spoilers')->default(false);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'tmdb_id', 'media_type']);
            $table->index(['tmdb_id', 'media_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
