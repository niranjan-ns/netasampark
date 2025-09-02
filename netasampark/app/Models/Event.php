<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'type',
        'status',
        'start_time',
        'end_time',
        'location',
        'coordinates',
        'attendees',
        'settings',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'attendees' => 'array',
        'settings' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
