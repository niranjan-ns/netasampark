<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'campaign_id',
        'message_id',
        'type',
        'direction',
        'from',
        'to',
        'content',
        'metadata',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'replied_at',
        'cost',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'cost' => 'decimal:4',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
