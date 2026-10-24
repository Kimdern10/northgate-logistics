<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A service shown on the website, e.g. "Air Freight".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property string $summary
 * @property string $description
 * @property list<string>|null $features
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'icon', 'summary', 'description', 'features', 'sort_order', 'is_active'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Icons the owner can choose from (drawn by the <x-icon> component).
     *
     * @var array<string, string>
     */
    public const array ICONS = [
        'plane' => 'Plane',
        'ship' => 'Ship',
        'truck' => 'Truck',
        'warehouse' => 'Warehouse',
        'cart' => 'Shopping cart',
        'box' => 'Box',
        'globe' => 'Globe',
        'shield' => 'Shield',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
