<?php

namespace App\Models;

use Database\Factories\AffiliateClickFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $service_name
 * @property string $service_id
 * @property int $tmdb_id
 * @property string $media_type
 * @property string $link
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'service_name', 'service_id', 'tmdb_id', 'media_type', 'link', 'ip_address'])]
class AffiliateClick extends Model
{
    /** @use HasFactory<AffiliateClickFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
