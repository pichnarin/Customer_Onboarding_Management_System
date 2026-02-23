<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageProgress extends Model
{
    use HasUuids;

    protected $fillable = [
        'assignment_id',
        'stage_id',
        'status',
        'progress_percentage',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'assignment_id' => 'string',
        'stage_id' => 'string',
        'progress_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
}
