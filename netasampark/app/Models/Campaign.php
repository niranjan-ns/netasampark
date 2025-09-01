<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'type',
        'status',
        'content',
        'target_audience',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'replies_count',
        'scheduled_at',
        'started_at',
        'completed_at',
        'settings',
    ];

    protected $casts = [
        'content' => 'array',
        'target_audience' => 'array',
        'settings' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
