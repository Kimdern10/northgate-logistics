<?php

use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Support\Countries;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('Reports')] class extends Component {
    /**
     * @var array<string, string>
     */
    public const array PERIODS = [
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        '90' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
    ];

    #[Url(except: '30')]
    public string $period = '30';

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        $now = CarbonImmutable::now();

        return match ($this->period) {
            '7' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            '90' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
        };
    }

    /**
     * Shipments booked in the period.
     *
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function booked(): Collection
    {
        [$from, $to] = $this->range();

        return Shipment::query()->whereBetween('created_at', [$from, $to])->orderBy('created_at')->get();
    }

    /**
     * Shipments delivered in the period (whenever they were booked).
     *
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function delivered(): Collection
    {
        [$from, $to] = $this->range();

        return Shipment::query()->where('status', ShipmentStatus::Delivered)->whereBetween('delivered_at', [$from, $to])->get();
    }

    /**
     * @return array<string, int|float|string|null>
     */
    #[Computed]
    public function figures(): array
    {
        [$from, $to] = $this->range();
        $booked = $this->booked->reject(fn (Shipment $shipment): bool => $shipment->status === ShipmentStatus::Cancelled);
        $withEstimate = $this->delivered->filter(fn (Shipment $shipment): bool => $shipment->estimated_delivery_date !== null);
        $onTime = $withEstimate->filter(fn (Shipment $shipment): bool => $shipment->delivered_at->toDateString() <= $shipment->estimated_delivery_date->toDateString());

        return [
            'bookings' => $booked->count(),
            'online' => $booked->where('source', 'website')->count(),
            'booked_value' => (int) $booked->sum('price_cents'),
            'received' => (int) Shipment::query()->where('payment_status', PaymentStatus::Paid)->whereBetween('paid_at', [$from, $to])->sum('price_cents'),
            'delivered' => $this->delivered->count(),
            'on_time_percent' => $withEstimate->isEmpty() ? null : (int) round($onTime->count() / $withEstimate->count() * 100),
            'average_days' => $this->delivered->isEmpty() ? null : round($this->delivered->avg(fn (Shipment $shipment): float => $shipment->created_at->diffInHours($shipment->delivered_at) / 24), 1),
            'on_hold' => Shipment::query()->where('status', ShipmentStatus::OnHold)->count(),
        ];
    }

    /**
     * Bookings per day (or per month for the whole year), for the bar chart.
     *
     * @return list<array{label: string, count: int, value: int}>
     */
    #[Computed]
    public function chart(): array
    {
        [$from, $to] = $this->range();
        $byMonth = $this->period === 'this_year';
        $format = $byMonth ? 'Y-m' : 'Y-m-d';
        $groups = $this->booked->groupBy(fn (Shipment $shipment): string => $shipment->created_at->format($format));

        $bars = [];
        $cursor = $from;

        while ($cursor <= $to) {
            $day = $groups->get($cursor->format($format), collect());
            $bars[] = ['label' => $cursor->format($byMonth ? 'M' : 'j M'), 'count' => $day->count(), 'value' => (int) $day->sum('price_cents')];
            $cursor = $byMonth ? $cursor->addMonth() : $cursor->addDay();
        }

        return $bars;
    }

    /**
     * @return Collection<int, array{route: string, count: int, value: int}>
     */
    #[Computed]
    public function topRoutes(): Collection
    {
        return $this->booked
            ->groupBy(fn (Shipment $shipment): string => $shipment->sender_country.'>'.$shipment->receiver_country)
            ->map(function (Collection $group, string $key): array {
                [$from, $to] = explode('>', $key);

                return ['route' => Countries::name($from).' → '.Countries::name($to), 'count' => $group->count(), 'value' => (int) $group->sum('price_cents')];
            })
            ->sortByDesc('count')
            ->take(8)
            ->values();
    }

    /**
     * @return Collection<int, array{service: string, count: int, value: int, average_lb: float}>
     */
    #[Computed]
    public function byService(): Collection
    {
        return $this->booked
            ->groupBy(fn (Shipment $shipment): string => $shipment->service_level->label())
            ->map(fn (Collection $group, string $service): array => [
                'service' => $service,
                'count' => $group->count(),
                'value' => (int) $group->sum('price_cents'),
                'average_lb' => round((float) $group->avg('weight_lb'), 1),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Parcels past their expected date: delivered late in the period, or still on the way.
     *
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function late(): Collection
    {
        $stillMoving = Shipment::query()
            ->whereNotIn('status', [ShipmentStatus::Delivered, ShipmentStatus::Cancelled])
            ->whereDate('estimated_delivery_date', '<', now()->toDateString())
            ->get();

        $deliveredLate = $this->delivered->filter(fn (Shipment $shipment): bool => $shipment->estimated_delivery_date !== null
            && $shipment->delivered_at->toDateString() > $shipment->estimated_delivery_date->toDateString());

        return $stillMoving->concat($deliveredLate)->sortBy('estimated_delivery_date')->take(10)->values();
    }

    public function export(): StreamedResponse
    {
        [$from, $to] = $this->range();
        $shipments = $this->booked;

        return response()->streamDownload(function () use ($shipments): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tracking number', 'Booked', 'Status', 'Service', 'From', 'To', 'Weight (lb)', 'Price (USD)', 'Payment', 'Paid on', 'Expected by', 'Delivered on', 'On time', 'Booked online']);

            foreach ($shipments as $shipment) {
                $onTime = $shipment->delivered_at && $shipment->estimated_delivery_date
                    ? ($shipment->delivered_at->toDateString() <= $shipment->estimated_delivery_date->toDateString() ? 'yes' : 'no')
                    : '';

                fputcsv($out, [
                    $shipment->tracking_number,
                    $shipment->created_at->format('Y-m-d H:i'),
                    $shipment->status->label(),
                    $shipment->service_level->label(),
                    $shipment->origin(),
                    $shipment->destination(),
                    $shipment->weight_lb,
                    Money::toDecimal($shipment->price_cents),
                    $shipment->payment_status->label(),
                    $shipment->paid_at?->format('Y-m-d') ?? '',
                    $shipment->estimated_delivery_date?->format('Y-m-d') ?? '',
                    $shipment->delivered_at?->format('Y-m-d') ?? '',
                    $onTime,
                    $shipment->isFromWebsite() ? 'yes' : 'no',
                ]);
            }

            fclose($out);
        }, 'northgate-shipments-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Reports</flux:heading>
            <flux:text>{{ $this->range()[0]->format('j M Y') }} to {{ $this->range()[1]->format('j M Y') }}</flux:text>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <div class="w-48">
                <flux:select wire:model.live="period">
                    @foreach ($this::PERIODS as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button icon="arrow-down-tray" wire:click="export">Download spreadsheet (CSV)</flux:button>
        </div>
    </div>

    @php($f = $this->figures)
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Bookings', number_format($f['bookings']), $f['bookings'] ? round($f['online'] / $f['bookings'] * 100).'% booked online' : 'No bookings yet'],
            ['Booked value', Money::format($f['booked_value']), 'Price of parcels booked'],
            ['Money received', Money::format($f['received']), 'Payments recorded in the period'],
            ['Delivered', number_format($f['delivered']), $f['average_days'] !== null ? 'Average '.$f['average_days'].' days door to door' : 'None delivered yet'],
            ['On time', $f['on_time_percent'] !== null ? $f['on_time_percent'].'%' : '—', 'Delivered by the expected date'],
            ['On hold now', number_format($f['on_hold']), 'Need attention'],
        ] as [$label, $value, $hint])
            <flux:card>
                <flux:text>{{ $label }}</flux:text>
                <div class="text-2xl font-semibold">{{ $value }}</div>
                <flux:text class="text-xs">{{ $hint }}</flux:text>
            </flux:card>
        @endforeach
    </div>

    <flux:card class="flex flex-col gap-3">
        <flux:heading size="lg">Bookings {{ $period === 'this_year' ? 'per month' : 'per day' }}</flux:heading>
        @php($bars = $this->chart)
        @php($max = max(1, max(array_column($bars, 'count') ?: [0])))
        <div class="flex h-48 items-end gap-[2px]" role="img" aria-label="Bar chart of bookings">
            @foreach ($bars as $bar)
                <div class="group relative flex h-full flex-1 flex-col justify-end" title="{{ $bar['label'] }}: {{ $bar['count'] }} {{ str('booking')->plural($bar['count']) }}, {{ Money::format($bar['value']) }}">
                    <div class="rounded-t bg-orange-500/85 transition group-hover:bg-orange-600" style="height: {{ $bar['count'] ? max(4, $bar['count'] / $max * 100) : 0 }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between text-xs text-zinc-500">
            <span>{{ $bars[0]['label'] ?? '' }}</span>
            <span>Most in one {{ $period === 'this_year' ? 'month' : 'day' }}: {{ $max }}</span>
            <span>{{ $bars[count($bars) - 1]['label'] ?? '' }}</span>
        </div>
    </flux:card>

    <div class="grid gap-6 xl:grid-cols-2">
        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Top routes</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Route</flux:table.column>
                    <flux:table.column align="end">Parcels</flux:table.column>
                    <flux:table.column align="end">Value</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->topRoutes as $row)
                        <flux:table.row>
                            <flux:table.cell>{{ $row['route'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['count'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ Money::format($row['value']) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="3">No bookings in this period.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">By service</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Service</flux:table.column>
                    <flux:table.column align="end">Parcels</flux:table.column>
                    <flux:table.column align="end">Avg weight</flux:table.column>
                    <flux:table.column align="end">Value</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->byService as $row)
                        <flux:table.row>
                            <flux:table.cell>{{ $row['service'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['count'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['average_lb'] }} lb</flux:table.cell>
                            <flux:table.cell align="end">{{ Money::format($row['value']) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="4">No bookings in this period.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <flux:card class="flex flex-col gap-3">
        <flux:heading size="lg">Late parcels</flux:heading>
        <flux:text>Still on the way after their expected date, or delivered late in this period.</flux:text>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Tracking no.</flux:table.column>
                <flux:table.column>Route</flux:table.column>
                <flux:table.column>Expected</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->late as $shipment)
                    <flux:table.row :key="$shipment->id">
                        <flux:table.cell><flux:link :href="route('admin.shipments.show', $shipment)" wire:navigate>{{ $shipment->tracking_number }}</flux:link></flux:table.cell>
                        <flux:table.cell>{{ $shipment->sender_city }} → {{ $shipment->receiver_city }}</flux:table.cell>
                        <flux:table.cell>{{ $shipment->estimated_delivery_date?->format('j M Y') }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="4">Nothing late. 🎉</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
