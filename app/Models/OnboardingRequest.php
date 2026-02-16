<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_code',
        'client_id',
        'system_id',
        'created_by_user_id',
        'priority',
        'status',
        'notes',
        'expected_start_date',
        'expected_end_date',
        'actual_start_date',
        'actual_end_date',
    ];

    protected $casts = [
        'id'                  => 'string',
        'client_id'           => 'string',
        'system_id'           => 'string',
        'created_by_user_id'  => 'string',
        'expected_start_date' => 'date',
        'expected_end_date'   => 'date',
        'actual_start_date'   => 'date',
        'actual_end_date'     => 'date',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'commentable_id')
                    ->where('commentable_type', 'onboarding_request');
    }
}
