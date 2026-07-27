<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->string('device_name', 100)->nullable()->after('cinesrc_server_id');
            $table->timestamp('last_watched_at')->nullable()->after('device_name');
        });
    }

    public function down(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'last_watched_at']);
        });
    }
};
