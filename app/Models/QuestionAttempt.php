<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAttempt extends Model
{
    protected $fillable = [
        'session_id',
        'question_id',
        'selected',
        'is_correct',
        'checked_at',
    ];

    protected $casts = [
        'selected' => 'array',
        'is_correct' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

