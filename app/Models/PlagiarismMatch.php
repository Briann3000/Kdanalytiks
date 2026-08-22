<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlagiarismMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'source_url',
        'source_title',
        'source_domain',
        'matched_text',
        'original_snippet',
        'similarity_score',
        'start_offset',
        'end_offset',
        'match_type',
        'is_excluded',
    ];

    protected $casts = [
        'similarity_score' => 'float',
        'start_offset' => 'integer',
        'end_offset' => 'integer',
        'is_excluded' => 'boolean',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(PlagiarismScan::class, 'scan_id');
    }
}
