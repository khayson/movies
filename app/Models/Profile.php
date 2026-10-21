<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A viewer on an account, with its own watch history, episode progress, My List and favorites.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $avatar_color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'avatar_color'])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    public const MAX_PER_ACCOUNT = 5;

    /**
     * Avatar color keys mapped to their Tailwind background classes.
     *
     * @var array<string, string>
     */
    public const AVATAR_COLORS = [
        'red' => 'bg-red-600',
        'amber' => 'bg-amber-500',
        'emerald' => 'bg-emerald-600',
        'sky' => 'bg-sky-600',
        'violet' => 'bg-violet-600',
        'pink' => 'bg-pink-600',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function avatarClass(): string
    {
        return self::AVATAR_COLORS[$this->avatar_color] ?? self::AVATAR_COLORS['red'];
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
