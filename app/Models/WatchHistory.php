<?php

namespace App\Models;

use Database\Factories\WatchHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
#[Fillable(['user_id', 'tmdb_id', 'media_type', 'title', 'poster_path', 'progress_seconds', 'duration_seconds', 'season', 'episode'])]
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

    public function progressPercent(): int
    {
        if ($this->duration_seconds === 0) {
            return 0;
        }

        return (int) round(($this->progress_seconds / $this->duration_seconds) * 100);
    }
}
