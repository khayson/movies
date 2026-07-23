<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
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
 * @property int $rating
 * @property string|null $body
 * @property bool $contains_spoilers
 * @property int $helpful_count
 */
#[Fillable(['user_id', 'tmdb_id', 'media_type', 'title', 'rating', 'body', 'contains_spoilers'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
