<?php

namespace App\Models;

use App\Support\Countries;
use Database\Factories\ShipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One customs line: what is in the parcel, how many, and what each is worth.
 *
 * @property int $id
 * @property int $shipment_id
 * @property string $description
 * @property int $quantity
 * @property int $unit_value_cents
 * @property string $origin_country
 * @property string|null $hs_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['description', 'quantity', 'unit_value_cents', 'origin_country', 'hs_code'])]
class ShipmentItem extends Model
{
    /** @use HasFactory<ShipmentItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function totalValueCents(): int
    {
        return $this->quantity * $this->unit_value_cents;
    }

    public function originName(): string
    {
        return Countries::name($this->origin_country);
    }
}
