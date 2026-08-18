<?php

namespace App\Models;

use Database\Factories\WatchHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $tmdb_id
 * @property string $media_type
 * @property string $title
 * @property string|null $poster_path
 * @property int $progress_seconds
 * @property int $duration_seconds
 * @property int|null $season
 * @property int|null $episode
 * @property string|null $last_server
 * @property string|null $cinesrc_server_id
 * @property string|null $device_name
 * @property Carbon|null $last_watched_at
 * @property bool $is_private
 */
#[Fillable(['user_id', 'tmdb_id', 'media_type', 'title', 'poster_path', 'progress_seconds', 'duration_seconds', 'season', 'episode', 'last_server', 'cinesrc_server_id', 'device_name', 'last_watched_at', 'is_private'])]
class WatchHistory extends Model
{
    /** @use HasFactory<WatchHistoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_watched_at' => 'datetime',
            'is_private' => 'boolean',
        ];
    }

    /**
     * @param  Builder<WatchHistory>  $query
     * @return Builder<WatchHistory>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    public function progressPercent(): int
    {
        if ($this->duration_seconds === 0) {
            return 0;
        }

        return (int) round(($this->progress_seconds / $this->duration_seconds) * 100);
    }

    public function formattedProgress(): string
    {
        return gmdate($this->progressSeconds >= 3600 ? 'H:i:s' : 'i:s', $this->progress_seconds);
    }

    public function formattedDuration(): string
    {
        return gmdate($this->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $this->duration_seconds);
    }
}
