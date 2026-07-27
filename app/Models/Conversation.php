<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_one_id
 * @property int $user_two_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_one_id', 'user_two_id', 'last_message_at'])]
class Conversation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function otherUser(int $userId): User
    {
        return $this->user_one_id === $userId
            ? $this->userTwo
            : $this->userOne;
    }

    public function involvedUser(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }

    public static function between(int $userA, int $userB): ?self
    {
        $ids = [min($userA, $userB), max($userA, $userB)];

        return static::where('user_one_id', $ids[0])
            ->where('user_two_id', $ids[1])
            ->first();
    }

    public static function findOrStartBetween(int $userA, int $userB): self
    {
        $ids = [min($userA, $userB), max($userA, $userB)];

        return static::firstOrCreate(
            ['user_one_id' => $ids[0], 'user_two_id' => $ids[1]],
        );
    }
}
