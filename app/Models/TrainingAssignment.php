<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingAssignment extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'onboarding_request_id',
        'trainer_id',
        'assigned_by_user_id',
        'assigned_at',
        'status',
        'notes',
        'accepted_at',
        'started_at',
        'completed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'onboarding_request_id' => 'string',
        'trainer_id' => 'string',
        'assigned_by_user_id' => 'string',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function onboardingRequest(): BelongsTo
    {
        return $this->belongsTo(OnboardingRequest::class);
    }

    public static function hasActiveAssignmentForSystem(string $systemId): bool
    {
        return self::whereIn('status', ['assigned', 'accepted', 'in_progress'])
            ->whereHas('onboardingRequest', function ($query) use ($systemId) {
                $query->where('system_id', $systemId);
            })
            ->exists();
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'assignment_id');
    }

    public function stageProgress(): HasMany
    {
        return $this->hasMany(StageProgress::class, 'assignment_id');
    }


    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'commentable_id')
            ->where('commentable_type', 'assignment');
    }
}
