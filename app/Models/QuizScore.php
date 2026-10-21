<?php

namespace App\Models;

use Database\Factories\QuizScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizScore extends Model
{
    /** @use HasFactory<QuizScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_type',
        'score',
        'total',
        'time_seconds',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
