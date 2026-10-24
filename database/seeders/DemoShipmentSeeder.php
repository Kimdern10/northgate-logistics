<?php

namespace Database\Seeders;

use App\Enums\EnquiryStatus;
use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Models\Enquiry;
use App\Models\Shipment;
use App\Services\RateCalculator;
use App\Support\Countries;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Sample shipments at every stage, with realistic timelines, for local testing only.
 * Demo tracking numbers NT1000000001 to NT1000000005 are easy to type in.
 */
class DemoShipmentSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array HUBS = [
        'New York' => 'JFK Airport Hub',
        'Los Angeles' => 'Los Angeles Hub',
        'Miami' => 'Miami Hub',
        'Chicago' => 'Chicago Branch',
        'Houston' => 'Houston Branch',
        'Dallas' => 'Dallas Fulfillment Center',
        'Austin' => 'Dallas Fulfillment Center',
        'Atlanta' => 'Atlanta Branch',
        'Seattle' => 'Los Angeles Hub',
        'Boston' => 'JFK Airport Hub',
        'GB' => 'London Heathrow Partner Hub',
        'AE' => 'Dubai Partner Hub',
        'HK' => 'Hong Kong Partner Hub',
        'CN' => 'Hong Kong Partner Hub',
    ];

    public function run(): void
    {
        // [tracking number or null, from city, from country, to city, to country, service, stage reached, contents, kg]
        $shipments = [
            ['NT1000000001', 'New York', 'US', 'London', 'GB', ServiceLevel::Express, 3, 'Fashion samples', 4.5],
            ['NT1000000002', 'Chicago', 'US', 'Houston', 'US', ServiceLevel::Standard, 7, 'Legal documents', 0.8],
            ['NT1000000003', 'Hong Kong', 'HK', 'Los Angeles', 'US', ServiceLevel::Economy, 6, 'Phone accessories (2 cartons)', 64],
            ['NT1000000004', 'Boston', 'US', 'Toronto', 'CA', ServiceLevel::Express, 0, 'Gift box: clothes and snacks', 6.2],
            ['NT1000000005', 'Houston', 'US', 'Dubai', 'AE', ServiceLevel::Standard, -1, 'Oil field spare parts', 18],
            [null, 'London', 'GB', 'Miami', 'US', ServiceLevel::Express, 7, 'Medical textbooks', 7.5],
            [null, 'New York', 'US', 'Manchester', 'GB', ServiceLevel::Standard, 4, 'Denim samples', 22],
            [null, 'Miami', 'US', 'São Paulo', 'BR', ServiceLevel::Economy, 2, 'Household goods', 140],
            [null, 'Dubai', 'AE', 'Atlanta', 'US', ServiceLevel::Express, 5, 'Laptop and accessories', 3.2],
            [null, 'Dallas', 'US', 'Seattle', 'US', ServiceLevel::Standard, 7, 'Online store orders', 12],
            [null, 'Los Angeles', 'US', 'San Francisco', 'US', ServiceLevel::Express, 6, 'Contracts', 0.5],
            [null, 'Atlanta', 'US', 'Mexico City', 'MX', ServiceLevel::Standard, 1, 'Skincare stock', 9],
            [null, 'Shenzhen', 'CN', 'Dallas', 'US', ServiceLevel::Economy, 3, 'Solar panels', 210],
            [null, 'Seattle', 'US', 'Tokyo', 'JP', ServiceLevel::Express, 7, 'Art prints', 2.4],
            [null, 'Austin', 'US', 'London', 'GB', ServiceLevel::Standard, 0, 'Leather bags', 5],
            [null, 'New York', 'US', 'Berlin', 'DE', ServiceLevel::Express, 2, 'Passport application', 0.4],
            [null, 'Los Angeles', 'US', 'Dubai', 'AE', ServiceLevel::Standard, 7, 'Cosmetics', 15],
            [null, 'London', 'GB', 'Chicago', 'US', ServiceLevel::Express, 4, 'Car parts', 11],
        ];

        $names = ['Emily Carter', 'James Rodriguez', 'Olivia Bennett', 'Michael Brooks', 'Sophia Martinez', 'Ethan Walker', 'Sarah Thompson', 'David Kim', 'Ava Mitchell', 'Noah Patel', 'Grace Sullivan', 'Lucas Rivera', 'Chloe Anderson', 'Laura Chen', 'Ryan O\'Connor', 'Hannah Schmidt'];
        $calculator = app(RateCalculator::class);

        foreach ($shipments as $index => [$number, $fromCity, $fromCountry, $toCity, $toCountry, $level, $stage, $contents, $kg]) {
            $startedAt = now()->subDays(match (true) {
                $stage < 0 => 6,
                $stage === 0 => 0,
                default => $stage + 2,
            })->setTime(9, 0)->addMinutes($index * 7);

            $quote = $calculator->quote($level, $fromCountry, $toCountry, $kg);
            $sender = $names[$index % count($names)];
            $receiver = $names[($index + 5) % count($names)];

            $shipment = Shipment::query()->create([
                'tracking_number' => $number ?? Shipment::newTrackingNumber(),
                'status' => ShipmentStatus::Booked,
                'service_level' => $level,
                'source' => $index % 3 === 0 ? 'website' : 'admin',
                'sender_name' => $sender,
                'sender_email' => str($sender)->slug('.')->value().'@example.com',
                'sender_phone' => '+1 (212) 555-'.str_pad((string) (100 + $index * 7), 4, '0', STR_PAD_LEFT),
                'sender_address' => (120 + $index * 3).' '.['W 34th St', 'Market St', 'Madison Ave', 'Ocean Dr', 'Peachtree St'][$index % 5],
                'sender_city' => $fromCity,
                'sender_country' => $fromCountry,
                'receiver_name' => $receiver,
                'receiver_email' => $index % 2 === 0 ? str($receiver)->slug('.')->value().'@example.org' : null,
                'receiver_phone' => '+1 (415) 555-'.str_pad((string) (200 + $index * 9), 4, '0', STR_PAD_LEFT),
                'receiver_address' => (3 + $index).' '.['High Street', 'Main Street', 'Park Lane', 'Station Road'][$index % 4],
                'receiver_city' => $toCity,
                'receiver_country' => $toCountry,
                'contents' => $contents,
                'pieces' => $kg > 50 ? 3 : 1,
                'weight_kg' => $kg,
                'declared_value_cents' => (int) round($kg * 2500),
                'price_cents' => $quote?->totalCents() ?? 0,
                'pickup_date' => $startedAt->addDay()->toDateString(),
                'estimated_delivery_date' => $quote?->estimatedDeliveryDate($startedAt)->toDateString(),
                'created_at' => $startedAt,
            ]);

            $this->journey($shipment, $stage, $startedAt);
        }

        $this->enquiries();
    }

    /**
     * Create the tracking timeline up to the given stage (-1 = put on hold after reaching the origin hub).
     */
    private function journey(Shipment $shipment, int $stage, \DateTimeInterface $startedAt): void
    {
        $origin = $shipment->origin();
        $destination = $shipment->destination();
        $hub = fn (string $city, string $country): string => (self::HUBS[$city] ?? self::HUBS[$country] ?? $city.' Gateway').', '.Countries::name($country);
        $fromHub = $hub($shipment->sender_city, $shipment->sender_country);
        $toHub = $hub($shipment->receiver_city, $shipment->receiver_country);
        $mode = $shipment->service_level === ServiceLevel::Economy ? 'vessel' : 'flight';

        $steps = [
            [ShipmentStatus::Booked, $origin, 'Pickup booked.'],
            [ShipmentStatus::PickedUp, $origin, 'Collected from the sender by our courier.'],
            [ShipmentStatus::AtHub, $fromHub, 'Arrived at our hub and passed security screening.'],
            [ShipmentStatus::InTransit, $fromHub, "Departed on {$mode} to {$shipment->receiver_city}."],
            [ShipmentStatus::AtHub, $toHub, 'Arrived at destination hub.'],
            [ShipmentStatus::CustomsClearance, $toHub, 'Cleared by customs.'],
            [ShipmentStatus::OutForDelivery, $destination, 'With our delivery driver. Expect a call before arrival.'],
            [ShipmentStatus::Delivered, $destination, 'Delivered. Signed by '.$shipment->receiverShortName()],
        ];

        if ($shipment->sender_country === $shipment->receiver_country) {
            unset($steps[5]);
        }

        $reached = $stage < 0 ? 2 : $stage;
        $time = CarbonImmutable::parse($startedAt);
        $hoursPerStep = $shipment->service_level === ServiceLevel::Economy ? 30 : 9;

        foreach (array_values($steps) as $index => [$status, $location, $description]) {
            if ($index > $reached) {
                break;
            }

            $shipment->events()->create(['status' => $status, 'location' => $location, 'description' => $description, 'happened_at' => $time]);
            $shipment->status = $status;
            $time = $time->addHours($hoursPerStep + $index);
        }

        if ($stage < 0) {
            $shipment->events()->create([
                'status' => ShipmentStatus::OnHold,
                'location' => $fromHub,
                'description' => 'Waiting for the commercial invoice from the sender before export.',
                'happened_at' => $time,
            ]);
            $shipment->status = ShipmentStatus::OnHold;
        }

        if ($shipment->status === ShipmentStatus::Delivered) {
            $shipment->delivered_at = $shipment->events()->first()?->happened_at;
            $shipment->received_by = $shipment->receiverShortName();
        }

        $shipment->save();
    }

    private function enquiries(): void
    {
        $enquiries = [
            ['fulfillment', 'Jessica Turner', 'Glow Naturals', 'jessica@glownaturals.example', 'We sell skincare on Shopify and Amazon, about 400 orders a month. Can you store our stock in Dallas and ship orders nationwide?', ['Monthly volume' => '400 orders'], EnquiryStatus::New],
            ['warehousing', 'Robert Hayes', 'Hayes Imports LLC', 'robert@hayesimports.example', 'Looking for 20 pallet spaces near JFK for 6 months, starting next month.', ['Monthly volume' => '20 pallets'], EnquiryStatus::New],
            ['quote', 'Mei Lin', 'Shenzhen Solar Co.', 'mei@szsolar.example', 'Please quote 2 x 40ft containers of solar panels from Shenzhen to the Port of Long Beach, door delivery to Phoenix, AZ.', ['Monthly volume' => '2 containers'], EnquiryStatus::Contacted],
            ['business', 'Brian Foster', 'Foster & Sons', 'brian@fostersons.example', 'We send about 50 parcels a week to the UK and Canada. Do you offer monthly invoicing and a volume discount?', ['Monthly volume' => '200 parcels'], EnquiryStatus::Won],
            ['general', 'Kayla Brooks', null, 'kayla.brooks@example.com', 'Can I send homemade cookies and hot sauce to my sister in London?', [], EnquiryStatus::New],
        ];

        foreach ($enquiries as $index => [$type, $name, $company, $email, $message, $details, $status]) {
            Enquiry::query()->create([
                'type' => $type,
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'phone' => '+234 80'.(55500100 + $index),
                'message' => $message,
                'details' => $details ?: null,
                'status' => $status,
                'created_at' => now()->subHours(3 + $index * 20),
            ]);
        }
    }
}
