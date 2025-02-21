<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Event extends Model
{
    protected $fillable = [
        'title',
        'category',
        'description',
        'start_date',
        'start_time',
        'end_time',
        'timezone',
        'event_type',
        'meeting_link',
        'venue_name',
        'address',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'hide_address',
        'max_participants',
        'min_age',
        'is_paid',
        'price',
        'currency',
        'rules',
        'notes',
        'creator_id',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_paid' => 'boolean',
        'hide_address' => 'boolean',
        'rules' => 'array',
        'coordinates' => 'array',
    ];

    protected $appends = ['is_full', 'is_creator'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withTimestamps();
    }

    public function getIsFullAttribute(): bool
    {
        if (! $this->max_participants) {
            return false;
        }

        return $this->current_participants >= $this->max_participants;
    }

    /**
     * Check if the authenticated user is the creator of this event
     */
    public function getIsCreatorAttribute(): bool
    {
        return Auth::id() === $this->creator_id;
    }

    /**
     * Check if a specific user is the creator of this event
     */
    public function isCreator(User $user): bool
    {
        return $user->id === $this->creator_id;
    }
}
