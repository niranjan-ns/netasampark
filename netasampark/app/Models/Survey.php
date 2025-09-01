<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'type',
        'status',
        'questions',
        'target_audience',
        'total_responses',
        'started_at',
        'ended_at',
        'settings',
    ];

    protected $casts = [
        'questions' => 'array',
        'target_audience' => 'array',
        'settings' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
