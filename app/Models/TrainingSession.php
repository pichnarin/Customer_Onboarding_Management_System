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
        'start_proof_media_id',
        'end_proof_media_id',
        'student_count',
        'cancellation_reason',
        'cancelled_by_user_id',
        'cancelled_at',
        'reschedule_reason',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'creator_id',
    ];

    protected $casts = [
        'id' => 'string',
        'assignment_id' => 'string',
        'stage_id' => 'string',
        'start_proof_media_id' => 'string',
        'end_proof_media_id' => 'string',
        'cancelled_by_user_id' => 'string',
        'scheduled_date'  => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time'   => 'datetime',
        'cancelled_at'      => 'datetime',
        'start_latitude'    => 'float',
        'start_longitude'   => 'float',
        'end_latitude'      => 'float',
        'end_longitude'     => 'float',
        'creator_id' => 'string',
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

    public function startProof(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'start_proof_media_id');
    }

    public function endProof(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'end_proof_media_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(SessionStudent::class, 'session_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
