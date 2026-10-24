<?php

namespace App\Models;

use App\Support\Countries;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A saved address in a customer's address book.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $label
 * @property string $name
 * @property string|null $company
 * @property string|null $email
 * @property string $phone
 * @property string $address
 * @property string $city
 * @property string $country
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['label', 'name', 'company', 'email', 'phone', 'address', 'city', 'country', 'is_default'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function summary(): string
    {
        return ($this->label ? $this->label.': ' : '').$this->name.', '.$this->city.', '.Countries::name($this->country);
    }

    /**
     * The address as shipment fields with a prefix, e.g. "sender_".
     *
     * @return array<string, string>
     */
    public function toShipmentFields(string $prefix): array
    {
        return collect(['name', 'company', 'email', 'phone', 'address', 'city', 'country'])
            ->mapWithKeys(fn (string $field): array => [$prefix.$field => (string) $this->{$field}])
            ->all();
    }
}
