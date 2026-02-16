<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'assignment_id',
        'stage_id',
        'session_title',
        'session_description',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'actual_start_time',
        'actual_end_time',
        'location_type',
        'meeting_link',
        'physical_location',
        'status',
        'completion_notes',
    ];

    protected $casts = [
        'id'                => 'string',
        'assignment_id'     => 'string',
        'stage_id'          => 'string',
        'scheduled_date'    => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time'   => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'assignment_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OnboardingStage::class, 'stage_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(SessionAttendee::class, 'session_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(SessionMaterial::class, 'session_id');
    }

    public function telegramMessages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class, 'related_session_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'commentable_id')
                    ->where('commentable_type', 'session');
    }
}
