<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'type',
        'prompt',
        'keyword',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    public function flags(): HasMany
    {
        return $this->hasMany(QuestionFlag::class);
    }

    /**
     * Hiển thị prompt: HTML từ CKEditor (đã lọc tag) hoặc text thuần (xuống dòng bằng br).
     */
    public static function renderPromptForDisplay(?string $prompt): string
    {
        if ($prompt === null || $prompt === '') {
            return '';
        }

        // Chỉ sanitize HTML để hiển thị; CKEditor có thể chứa ảnh dưới dạng <img ...>.
        if (preg_match('/^\s*<(p|h[1-6]|ul|ol|blockquote|div|figure|figcaption|strong|em|b|i|img)\b/i', $prompt)) {
            $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><h1><h2><h3><h4><blockquote><figure><figcaption><img>';

            return strip_tags($prompt, $allowed);
        }

        return nl2br(e($prompt), false);
    }

    /**
     * Bản rút gọn không HTML cho danh sách (giữ xuống dòng nhẹ).
     */
    public static function plainPromptPreview(?string $prompt): string
    {
        if ($prompt === null || $prompt === '') {
            return '';
        }

        $tmp = preg_replace('/<\/p>\s*<p\b[^>]*>/i', "\n", $prompt);
        $tmp = preg_replace('/<br\s*\/?>/i', "\n", $tmp);
        $plain = strip_tags($tmp);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim(preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{3,}/u", "\n\n", $plain)));

        return $plain;
    }
}

