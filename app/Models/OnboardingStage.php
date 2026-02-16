<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingStage extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'sequence_order',
        'estimated_duration_days',
        'system_id',
        'is_active',
    ];

    protected $casts = [
        'id'        => 'string',
        'system_id' => 'string',
        'is_active' => 'boolean',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'stage_id');
    }

    public function stageProgress(): HasMany
    {
        return $this->hasMany(StageProgress::class, 'stage_id');
    }
}
