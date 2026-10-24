<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Database\Factories\TrackingEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line on a shipment's tracking timeline.
 *
 * @property int $id
 * @property int $shipment_id
 * @property ShipmentStatus $status
 * @property string $location
 * @property string|null $description
 * @property Carbon $happened_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['status', 'location', 'description', 'happened_at'])]
class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'happened_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
