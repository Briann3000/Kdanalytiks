<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlagiarismScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'title',
        'original_filename',
        'file_path',
        'file_type',
        'content',
        'word_count',
        'character_count',
        'similarity_percentage',
        'ai_percentage',
        'exclude_quotes',
        'exclude_references',
        'exclude_citations',
        'exclude_small_matches',
        'min_words_threshold',
        'excluded_domains',
        'status',
        'error_message',
        'summary_metadata',
    ];

    protected $casts = [
        'similarity_percentage' => 'float',
        'ai_percentage' => 'float',
        'exclude_quotes' => 'boolean',
        'exclude_references' => 'boolean',
        'exclude_citations' => 'boolean',
        'exclude_small_matches' => 'boolean',
        'min_words_threshold' => 'integer',
        'excluded_domains' => 'array',
        'summary_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(PlagiarismMatch::class, 'scan_id');
    }

    public function activeMatches(): HasMany
    {
        return $this->hasMany(PlagiarismMatch::class, 'scan_id')->where('is_excluded', false);
    }

    /**
     * Get visual badge classification for similarity level
     */
    public function getSimilarityLevelAttribute(): string
    {
        if ($this->similarity_percentage <= 10) {
            return 'Low Risk';
        }
        if ($this->similarity_percentage <= 25) {
            return 'Moderate Risk';
        }
        return 'High Risk';
    }

    /**
     * Get visual badge classification for AI content
     */
    public function getAiLevelAttribute(): string
    {
        if ($this->ai_percentage <= 20) {
            return 'Likely Human';
        }
        if ($this->ai_percentage <= 50) {
            return 'Mixed AI & Human';
        }
        return 'Likely AI Generated';
    }
}
