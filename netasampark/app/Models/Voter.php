<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'voter_id',
        'name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'address',
        'constituency',
        'district',
        'state',
        'booth_number',
        'part_number',
        'serial_number',
        'demographics',
        'tags',
        'consent',
        'status',
        'last_contacted_at',
    ];

    protected $casts = [
        'demographics' => 'array',
        'tags' => 'array',
        'consent' => 'array',
        'date_of_birth' => 'date',
        'last_contacted_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
