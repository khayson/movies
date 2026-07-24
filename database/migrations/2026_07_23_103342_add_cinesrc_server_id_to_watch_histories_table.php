<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->string('cinesrc_server_id')->nullable()->after('last_server');
        });
    }

    public function down(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->dropColumn('cinesrc_server_id');
        });
    }
};
