<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Viewing tables and the columns (after user_id) that make a row unique.
     *
     * @var array<string, list<string>>
     */
    private const SCOPED_TABLES = [
        'watch_histories' => ['tmdb_id', 'media_type'],
        'watchlists' => ['tmdb_id', 'media_type'],
        'favorites' => ['tmdb_id', 'media_type'],
        'episode_watches' => ['tmdb_id', 'season_number', 'episode_number'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('avatar_color', 20)->default('red');
            $table->timestamps();
        });

        foreach (array_keys(self::SCOPED_TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('profile_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            });
        }

        DB::table('users')->select(['id', 'name'])->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $profileId = DB::table('profiles')->insertGetId([
                    'user_id' => $user->id,
                    'name' => Str::limit(Str::words((string) $user->name, 1, ''), 40, '') ?: 'Me',
                    'avatar_color' => 'red',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach (array_keys(self::SCOPED_TABLES) as $tableName) {
                    DB::table($tableName)->where('user_id', $user->id)->update(['profile_id' => $profileId]);
                }
            }
        });

        foreach (self::SCOPED_TABLES as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->dropUnique(['user_id', ...$columns]);
                $table->unique(['profile_id', ...$columns]);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Rows kept by additional profiles are removed, because the per-account
     * unique keys cannot hold the same title more than once.
     */
    public function down(): void
    {
        foreach (self::SCOPED_TABLES as $tableName => $columns) {
            DB::table($tableName)
                ->whereNotIn('profile_id', DB::table('profiles')->selectRaw('min(id)')->groupBy('user_id'))
                ->delete();

            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->dropUnique(['profile_id', ...$columns]);
                $table->unique(['user_id', ...$columns]);
                $table->dropConstrainedForeignId('profile_id');
            });
        }

        Schema::dropIfExists('profiles');
    }
};
