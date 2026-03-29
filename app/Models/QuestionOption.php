<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'text',
        'display_order',
        'is_correct',
        'correct_position',
        'select_group',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'display_order' => 'integer',
        'correct_position' => 'integer',
        'select_group' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

