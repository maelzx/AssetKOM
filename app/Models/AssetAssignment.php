<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssignmentStatus;
use Database\Factories\AssetAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'asset_id',
    'assignable_type',
    'assignable_id',
    'assigned_by',
    'assigned_at',
    'expected_return_at',
    'returned_at',
    'condition_out',
    'condition_in',
    'checkout_notes',
    'checkin_notes',
    'status',
    'active',
])]
class AssetAssignment extends Model
{
    /** @use HasFactory<AssetAssignmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'expected_return_at' => 'date',
            'returned_at' => 'datetime',
            'condition_out' => AssetCondition::class,
            'condition_in' => AssetCondition::class,
            'status' => AssignmentStatus::class,
            'active' => 'integer',
            'checkout_notes' => 'string',
            'checkin_notes' => 'string',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return $this->status === AssignmentStatus::Active;
    }

    public function isOverdue(): bool
    {
        return $this->isActive()
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
    }

    /**
     * @param  Builder<AssetAssignment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', AssignmentStatus::Active->value);
    }

    /**
     * @param  Builder<AssetAssignment>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->active()
            ->whereNotNull('expected_return_at')
            ->whereDate('expected_return_at', '<', now()->toDateString());
    }
}
