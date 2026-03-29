<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionFlag extends Model
{
    protected $fillable = [
        'visitor_id',
        'question_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

