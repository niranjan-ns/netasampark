<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'ticket_number',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'assigned_to',
        'created_by',
        'metadata',
        'due_at',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
