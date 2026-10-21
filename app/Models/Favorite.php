<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProfile;
use Database\Factories\FavoriteFactory;
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
 */
#[Fillable(['user_id', 'profile_id', 'tmdb_id', 'media_type', 'title', 'poster_path'])]
class Favorite extends Model
{
    /** @use HasFactory<FavoriteFactory> */
    use BelongsToProfile, HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
