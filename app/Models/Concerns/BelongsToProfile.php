<?php

namespace App\Models\Concerns;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Viewing data (history, progress, My List, favorites) belongs to one profile of an account.
 * Rows created with only a user_id are filed under that user's active profile.
 *
 * @property int|null $profile_id
 */
trait BelongsToProfile
{
    public static function bootBelongsToProfile(): void
    {
        static::creating(function (self $model): void {
            if ($model->profile_id === null) {
                $model->profile_id = User::query()->find($model->user_id)?->activeProfile()->id;
            }
        });
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
