<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use Database\Factories\MaintenanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'asset_id',
    'type',
    'title',
    'description',
    'vendor',
    'cost',
    'currency',
    'status',
    'scheduled_at',
    'completed_at',
    'performed_by',
    'notes',
    'created_by',
])]
class Maintenance extends Model
{
    /** @use HasFactory<MaintenanceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MaintenanceType::class,
            'status' => MaintenanceStatus::class,
            'currency' => Currency::class,
            'cost' => 'decimal:2',
            'scheduled_at' => 'date',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isOverdue(): bool
    {
        return $this->status === MaintenanceStatus::Scheduled
            && $this->scheduled_at !== null
            && $this->scheduled_at->isPast();
    }

    /**
     * @param  Builder<Maintenance>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [
            MaintenanceStatus::Scheduled->value,
            MaintenanceStatus::InProgress->value,
        ]);
    }
}
