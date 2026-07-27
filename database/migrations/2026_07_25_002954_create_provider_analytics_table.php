<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('region', 10)->default('unknown');
            $table->unsignedTinyInteger('hour_bucket');
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->unsignedInteger('buffer_count')->default(0);
            $table->unsignedInteger('avg_load_ms')->default(0);
            $table->date('date');
            $table->timestamps();

            $table->unique(['provider', 'region', 'hour_bucket', 'date']);
            $table->index(['provider', 'hour_bucket']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_analytics');
    }
};
