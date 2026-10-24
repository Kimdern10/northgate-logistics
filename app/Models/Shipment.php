<?php

namespace App\Models;

use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Support\Countries;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A parcel or consignment moving through the network.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $tracking_number
 * @property ShipmentStatus $status
 * @property ServiceLevel $service_level
 * @property string $source
 * @property string $sender_name
 * @property string|null $sender_company
 * @property string|null $sender_email
 * @property string $sender_phone
 * @property string $sender_address
 * @property string $sender_city
 * @property string $sender_country
 * @property string $receiver_name
 * @property string|null $receiver_company
 * @property string|null $receiver_email
 * @property string $receiver_phone
 * @property string $receiver_address
 * @property string $receiver_city
 * @property string $receiver_country
 * @property string $contents
 * @property int $pieces
 * @property float $weight_kg
 * @property int|null $length_cm
 * @property int|null $width_cm
 * @property int|null $height_cm
 * @property int $declared_value_cents
 * @property int $price_cents
 * @property Carbon|null $pickup_date
 * @property Carbon|null $estimated_delivery_date
 * @property Carbon|null $delivered_at
 * @property string|null $received_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'status', 'service_level', 'source',
    'sender_name', 'sender_company', 'sender_email', 'sender_phone', 'sender_address', 'sender_city', 'sender_country',
    'receiver_name', 'receiver_company', 'receiver_email', 'receiver_phone', 'receiver_address', 'receiver_city', 'receiver_country',
    'contents', 'pieces', 'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'declared_value_cents', 'price_cents',
    'pickup_date', 'estimated_delivery_date', 'delivered_at', 'received_by', 'notes',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'booked',
        'source' => 'admin',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment): void {
            $shipment->tracking_number ??= self::newTrackingNumber();
        });

        // Emails are matched to customer accounts, so keep them in one form.
        static::saving(function (Shipment $shipment): void {
            $shipment->sender_email = $shipment->sender_email ? strtolower(trim($shipment->sender_email)) : null;
            $shipment->receiver_email = $shipment->receiver_email ? strtolower(trim($shipment->receiver_email)) : null;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'service_level' => ServiceLevel::class,
            'weight_kg' => 'float',
            'pickup_date' => 'date',
            'estimated_delivery_date' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Newest update first.
     *
     * @return HasMany<TrackingEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderByDesc('happened_at')->orderByDesc('id');
    }

    /**
     * The customer account that booked it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Customers who added it to their account.
     *
     * @return BelongsToMany<User, $this>
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shipment_watches')->withTimestamps();
    }

    /**
     * A number like "NG4827301956": two letters and ten digits, easy to read out over the phone.
     */
    public static function newTrackingNumber(): string
    {
        do {
            $number = 'NG'.random_int(1, 9).Str::padLeft((string) random_int(0, 999_999_999), 9, '0');
        } while (static::query()->where('tracking_number', $number)->exists());

        return $number;
    }

    /**
     * Tidy what a visitor types: spaces and dashes removed, upper case.
     */
    public static function normaliseTrackingNumber(string $number): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $number));
    }

    public static function findByTrackingNumber(string $number): ?self
    {
        $number = self::normaliseTrackingNumber($number);

        return $number === '' ? null : static::query()->where('tracking_number', $number)->first();
    }

    public function origin(): string
    {
        return $this->sender_city.', '.Countries::name($this->sender_country);
    }

    public function destination(): string
    {
        return $this->receiver_city.', '.Countries::name($this->receiver_country);
    }

    public function isFromWebsite(): bool
    {
        return $this->source === 'website';
    }

    public function dimensions(): ?string
    {
        return $this->length_cm && $this->width_cm && $this->height_cm
            ? "{$this->length_cm} × {$this->width_cm} × {$this->height_cm} cm"
            : null;
    }

    /**
     * Only the first name and initial, so the public tracking page does not show who the parcel is for.
     */
    public function receiverShortName(): string
    {
        $parts = preg_split('/\s+/', trim($this->receiver_name)) ?: [];
        $first = array_shift($parts) ?? '';
        $last = array_pop($parts);

        return $last ? $first.' '.mb_substr($last, 0, 1).'.' : $first;
    }

    /**
     * The people who get status emails: sender, receiver, the booking account and anyone watching it.
     *
     * @return list<string>
     */
    public function notifiableEmails(): array
    {
        return collect([$this->sender_email, $this->receiver_email, $this->user?->email])
            ->merge($this->watchers()->pluck('email'))
            ->filter()
            ->map(fn (string $email): string => strtolower($email))
            ->unique()
            ->values()
            ->all();
    }
}
