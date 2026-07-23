<?php

namespace App\Models;

use Database\Factories\WatchPartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $host_id
 * @property string $title
 * @property int $tmdb_id
 * @property string $media_type
 * @property string|null $poster_path
 * @property string $code
 * @property Carbon $starts_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['host_id', 'title', 'tmdb_id', 'media_type', 'poster_path', 'code', 'starts_at', 'is_active'])]
class WatchParty extends Model
{
    /** @use HasFactory<WatchPartyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WatchParty $party): void {
            if (empty($party->code)) {
                $party->code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }
}
