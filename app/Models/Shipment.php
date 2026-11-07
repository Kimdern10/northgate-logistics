<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Support\Countries;
use App\Support\Money;
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
 * @property float $weight_lb
 * @property int|null $length_in
 * @property int|null $width_in
 * @property int|null $height_in
 * @property int $declared_value_cents
 * @property int $price_cents
 * @property Carbon|null $pickup_date
 * @property Carbon|null $estimated_delivery_date
 * @property Carbon|null $delivered_at
 * @property int|null $base_fee_cents
 * @property int|null $weight_charge_cents
 * @property int|null $fuel_surcharge_cents
 * @property float|null $chargeable_weight_lb
 * @property PaymentStatus $payment_status
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property Carbon|null $paid_at
 * @property string|null $export_reason
 * @property string|null $received_by
 * @property string|null $delivery_photo_path
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'status', 'service_level', 'source',
    'sender_name', 'sender_company', 'sender_email', 'sender_phone', 'sender_address', 'sender_city', 'sender_country',
    'receiver_name', 'receiver_company', 'receiver_email', 'receiver_phone', 'receiver_address', 'receiver_city', 'receiver_country',
    'base_fee_cents', 'weight_charge_cents', 'fuel_surcharge_cents', 'chargeable_weight_lb',
    'payment_status', 'payment_method', 'payment_reference', 'paid_at',
    'contents', 'export_reason', 'pieces', 'weight_lb', 'length_in', 'width_in', 'height_in', 'declared_value_cents', 'price_cents',
    'pickup_date', 'estimated_delivery_date', 'delivered_at', 'received_by', 'notes',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    /**
     * Most tracking numbers looked up in one go, like UPS and FedEx allow.
     */
    public const int MAX_TRACKED_AT_ONCE = 10;

    /**
     * Why goods cross a border, as asked on customs forms.
     *
     * @var array<string, string>
     */
    public const array EXPORT_REASONS = [
        'sale' => 'Sale (sold to the receiver)',
        'gift' => 'Gift',
        'sample' => 'Commercial sample',
        'personal' => 'Personal belongings',
        'documents' => 'Documents',
        'return' => 'Return or repair',
    ];

    /**
     * How a shipment was paid.
     *
     * @var array<string, string>
     */
    public const array PAYMENT_METHODS = [
        'card' => 'Card (online)',
        'card_at_pickup' => 'Card at pickup',
        'cash' => 'Cash at pickup',
        'transfer' => 'Bank transfer',
        'account' => 'Business account (invoiced)',
    ];

    protected $attributes = [
        'status' => 'booked',
        'source' => 'admin',
        'payment_status' => 'unpaid',
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
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'chargeable_weight_lb' => 'float',
            'weight_lb' => 'float',
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
     * Customs lines for a shipment that crosses a border.
     *
     * @return HasMany<ShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class)->orderBy('id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function markPaid(string $method, ?string $reference = null): void
    {
        $this->forceFill([
            'payment_status' => PaymentStatus::Paid,
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_at' => now(),
        ])->save();
    }

    public function paymentMethodLabel(): ?string
    {
        return $this->payment_method ? (self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method)) : null;
    }

    public function crossesBorder(): bool
    {
        return strtoupper($this->sender_country) !== strtoupper($this->receiver_country);
    }

    public function exportReasonLabel(): ?string
    {
        return $this->export_reason ? (self::EXPORT_REASONS[$this->export_reason] ?? ucfirst($this->export_reason)) : null;
    }

    /**
     * Replace the customs lines with the ones typed in a form and set the declared value to their total.
     *
     * @param  list<array{description: string, quantity: int|string, unit_value: int|float|string, origin_country: string, hs_code?: string|null}>  $rows
     */
    public function replaceCustomsItems(array $rows): void
    {
        $this->items()->delete();

        foreach ($rows as $row) {
            $this->items()->create([
                'description' => $row['description'],
                'quantity' => (int) $row['quantity'],
                'unit_value_cents' => Money::toCents($row['unit_value']),
                'origin_country' => strtoupper($row['origin_country']),
                'hs_code' => filled($row['hs_code'] ?? null) ? $row['hs_code'] : null,
            ]);
        }

        $this->forceFill(['declared_value_cents' => (int) $this->items()->get()->sum(fn (ShipmentItem $item): int => $item->totalValueCents())])->save();
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
     * A number like "NT4827301956": two letters and ten digits, easy to read out over the phone.
     */
    public static function newTrackingNumber(): string
    {
        do {
            $number = 'NT'.random_int(1, 9).Str::padLeft((string) random_int(0, 999_999_999), 9, '0');
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

    /**
     * Pick out every "NT + 10 digits" in what a visitor typed, even with spaces or dashes inside
     * ("NT 482-730 1956"). Text that holds no full number is returned tidied as a single entry.
     *
     * @return list<string>
     */
    public static function parseTrackingNumbers(string $input): array
    {
        preg_match_all('/n\s*t(?:[\s\-]*\d){10}/i', $input, $matches);

        return array_values(collect($matches[0] ?: [$input])
            ->map(fn (string $number): string => self::normaliseTrackingNumber($number))
            ->filter()
            ->unique()
            ->all());
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
        return $this->length_in && $this->width_in && $this->height_in
            ? "{$this->length_in} × {$this->width_in} × {$this->height_in} in"
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
        return array_values(collect([$this->sender_email, $this->receiver_email, $this->user?->email])
            ->merge($this->watchers()->pluck('email'))
            ->filter()
            ->map(fn (string $email): string => strtolower($email))
            ->unique()
            ->all());
    }
}
