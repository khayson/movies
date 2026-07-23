<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property array<string, mixed>|null $preferences
 * @property Carbon|null $date_of_birth
 * @property bool $is_premium
 * @property Carbon|null $premium_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'preferences', 'date_of_birth', 'is_premium', 'premium_until'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'date_of_birth' => 'date',
            'is_premium' => 'boolean',
            'premium_until' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * @return HasMany<WatchHistory, $this>
     */
    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class)->latest('updated_at');
    }

    /**
     * @return HasMany<Watchlist, $this>
     */
    public function watchlist(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    /**
     * @return HasMany<EpisodeWatch, $this>
     */
    public function episodeWatches(): HasMany
    {
        return $this->hasMany(EpisodeWatch::class);
    }

    /**
     * @return HasMany<Collection, $this>
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class)->latest();
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function hasReviewed(int $tmdbId, string $mediaType): bool
    {
        return $this->reviews()->where('tmdb_id', $tmdbId)->where('media_type', $mediaType)->exists();
    }

    public function hasFavorited(int $tmdbId, string $mediaType): bool
    {
        return $this->favorites()->where('tmdb_id', $tmdbId)->where('media_type', $mediaType)->exists();
    }

    public function hasOnWatchlist(int $tmdbId, string $mediaType): bool
    {
        return $this->watchlist()->where('tmdb_id', $tmdbId)->where('media_type', $mediaType)->exists();
    }

    public function isAdult(): bool
    {
        return $this->date_of_birth && $this->date_of_birth->age >= 18;
    }

    public function canViewAdultContent(): bool
    {
        return $this->isAdult() && ($this->preferences['show_adult_content'] ?? false);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    /**
     * @return HasMany<UserNotification, $this>
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    /**
     * @return HasMany<WatchParty, $this>
     */
    public function watchParties(): HasMany
    {
        return $this->hasMany(WatchParty::class, 'host_id');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function isPremium(): bool
    {
        return $this->is_premium && ($this->premium_until === null || $this->premium_until->isFuture());
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
