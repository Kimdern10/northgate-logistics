<?php

namespace App\Models;

use App\Support\Countries;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An office, hub or warehouse shown on the coverage map.
 *
 * @property int $id
 * @property string $name
 * @property string $city
 * @property string $country
 * @property string $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $opening_hours
 * @property float $latitude
 * @property float $longitude
 * @property bool $is_hub
 * @property bool $is_warehouse
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'city', 'country', 'address', 'phone', 'email', 'opening_hours', 'latitude', 'longitude', 'is_hub', 'is_warehouse', 'is_active'])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_hub' => 'boolean',
            'is_warehouse' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_active', true)->orderByDesc('is_hub')->orderBy('country')->orderBy('city');
    }

    public function countryName(): string
    {
        return Countries::name($this->country);
    }

    public function kind(): string
    {
        return match (true) {
            $this->is_hub && $this->is_warehouse => 'Hub & warehouse',
            $this->is_hub => 'Regional hub',
            $this->is_warehouse => 'Warehouse',
            default => 'Branch office',
        };
    }

    /**
     * What the map needs for one pin.
     *
     * @return array{name: string, city: string, country: string, kind: string, address: string, phone: string|null, lat: float, lng: float, hub: bool}
     */
    public function toMapPin(): array
    {
        return [
            'name' => $this->name,
            'city' => $this->city,
            'country' => $this->countryName(),
            'kind' => $this->kind(),
            'address' => $this->address,
            'phone' => $this->phone,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'hub' => $this->is_hub,
        ];
    }
}
