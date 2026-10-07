<?php

namespace App\Models;

use Database\Factories\UserNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property int|null $tmdb_id
 * @property string|null $media_type
 * @property string|null $poster_path
 * @property string|null $link
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'type', 'title', 'message', 'tmdb_id', 'media_type', 'poster_path', 'link', 'read_at'])]
class UserNotification extends Model
{
    /** @use HasFactory<UserNotificationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for a specific visitor (authenticated user sees personal + broadcasts; guest sees broadcasts).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForVisitor(Builder $query, ?int $userId = null): Builder
    {
        if ($userId) {
            return $query->where(function (Builder $q) use ($userId): void {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            });
        }

        return $query->whereNull('user_id');
    }

    /**
     * Scope for public broadcast notifications.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBroadcast(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
