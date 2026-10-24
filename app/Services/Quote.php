<?php

namespace App\Services;

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The price of one shipment at one service level.
 */
final readonly class Quote
{
    public function __construct(
        public ServiceLevel $level,
        public Zone $zone,
        public float $actualWeightKg,
        public float $volumetricWeightKg,
        public float $chargeableWeightKg,
        public int $baseFeeCents,
        public int $weightChargeCents,
        public int $fuelSurchargeCents,
        public int $minDays,
        public int $maxDays,
    ) {}

    public function totalCents(): int
    {
        return $this->baseFeeCents + $this->weightChargeCents + $this->fuelSurchargeCents;
    }

    public function total(): string
    {
        return Money::format($this->totalCents());
    }

    public function transitTime(): string
    {
        return $this->minDays === $this->maxDays
            ? $this->minDays.' '.str('business day')->plural($this->minDays)
            : "{$this->minDays}–{$this->maxDays} business days";
    }

    /**
     * The latest expected delivery date, counting working days only.
     */
    public function estimatedDeliveryDate(?CarbonInterface $from = null): CarbonInterface
    {
        return CarbonImmutable::parse($from ?? now())->addWeekdays($this->maxDays);
    }
}
