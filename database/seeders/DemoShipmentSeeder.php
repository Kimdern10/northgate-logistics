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
 * Demo tracking numbers NG1000000001 to NG1000000005 are easy to type in.
 */
class DemoShipmentSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array HUBS = [
        'NG' => 'Lagos Airport Hub',
        'GB' => 'London Heathrow Hub',
        'US' => 'Houston Branch',
        'AE' => 'Dubai Hub',
        'CN' => 'Guangzhou Branch',
        'GH' => 'Accra Branch',
        'ZA' => 'Johannesburg Branch',
    ];

    public function run(): void
    {
        // [tracking number or null, from city, from country, to city, to country, service, stage reached, contents, kg]
        $shipments = [
            ['NG1000000001', 'Lagos', 'NG', 'London', 'GB', ServiceLevel::Express, 3, 'Fashion samples', 4.5],
            ['NG1000000002', 'Abuja', 'NG', 'Houston', 'US', ServiceLevel::Standard, 7, 'University documents', 0.8],
            ['NG1000000003', 'Guangzhou', 'CN', 'Lagos', 'NG', ServiceLevel::Economy, 6, 'Phone accessories (2 cartons)', 64],
            ['NG1000000004', 'Lagos', 'NG', 'Toronto', 'CA', ServiceLevel::Express, 0, 'Gift box: clothes and snacks', 6.2],
            ['NG1000000005', 'Port Harcourt', 'NG', 'Dubai', 'AE', ServiceLevel::Standard, -1, 'Oil field spare parts', 18],
            [null, 'London', 'GB', 'Accra', 'GH', ServiceLevel::Express, 7, 'Medical textbooks', 7.5],
            [null, 'Lagos', 'NG', 'Manchester', 'GB', ServiceLevel::Standard, 4, 'Ankara fabric rolls', 22],
            [null, 'Houston', 'US', 'Lagos', 'NG', ServiceLevel::Economy, 2, 'Household goods', 140],
            [null, 'Dubai', 'AE', 'Abuja', 'NG', ServiceLevel::Express, 5, 'Laptop and accessories', 3.2],
            [null, 'Lagos', 'NG', 'Accra', 'GH', ServiceLevel::Standard, 7, 'Hair products', 12],
            [null, 'Lagos', 'NG', 'Abuja', 'NG', ServiceLevel::Express, 6, 'Legal documents', 0.5],
            [null, 'Lagos', 'NG', 'Johannesburg', 'ZA', ServiceLevel::Standard, 1, 'Skincare stock', 9],
            [null, 'Guangzhou', 'CN', 'Accra', 'GH', ServiceLevel::Economy, 3, 'Solar panels', 210],
            [null, 'Lagos', 'NG', 'New York', 'US', ServiceLevel::Express, 7, 'Art prints', 2.4],
            [null, 'Kano', 'NG', 'London', 'GB', ServiceLevel::Standard, 0, 'Leather bags', 5],
            [null, 'Lagos', 'NG', 'Berlin', 'DE', ServiceLevel::Express, 2, 'Passport application', 0.4],
            [null, 'Lagos', 'NG', 'Dubai', 'AE', ServiceLevel::Standard, 7, 'Shea butter', 15],
            [null, 'London', 'GB', 'Lagos', 'NG', ServiceLevel::Express, 4, 'Car parts', 11],
        ];

        $names = ['Chiamaka Obi', 'Ibrahim Musa', 'Ngozi Adeyemi', 'Kelechi Nwosu', 'Aisha Lawal', 'Emeka Okonkwo', 'Sarah Thompson', 'David Mensah', 'Yusuf Abdullahi', 'Blessing Udo', 'Michael Brown', 'Zainab Sani', 'Oluwaseun Ade', 'Laura Chen', 'Kwame Boateng', 'Hannah Schmidt'];
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
                'sender_phone' => '+234 80'.str_pad((string) (31234567 + $index * 1111), 8, '0', STR_PAD_LEFT),
                'sender_address' => (12 + $index).' '.['Allen Avenue', 'Adeola Odeku Street', 'Herbert Macaulay Way', 'Oxford Street', 'Baker Street'][$index % 5],
                'sender_city' => $fromCity,
                'sender_country' => $fromCountry,
                'receiver_name' => $receiver,
                'receiver_email' => $index % 2 === 0 ? str($receiver)->slug('.')->value().'@example.org' : null,
                'receiver_phone' => '+44 7700 9'.str_pad((string) (10000 + $index * 37), 5, '0', STR_PAD_LEFT),
                'receiver_address' => (3 + $index).' '.['High Street', 'Main Road', 'Park Lane', 'Station Road'][$index % 4],
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
        $fromHub = (self::HUBS[$shipment->sender_country] ?? $shipment->sender_city.' Gateway').', '.Countries::name($shipment->sender_country);
        $toHub = (self::HUBS[$shipment->receiver_country] ?? $shipment->receiver_city.' Gateway').', '.Countries::name($shipment->receiver_country);
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
            ['fulfilment', 'Temi Johnson', 'Glow Naturals', 'temi@glownaturals.example', 'We sell skincare on Instagram and our website, about 400 orders a month. Can you store our stock in Lagos and deliver orders?', ['Monthly volume' => '400 orders'], EnquiryStatus::New],
            ['warehousing', 'Robert Ade', 'Ade Imports Ltd', 'robert@adeimports.example', 'Looking for 20 pallet spaces near Lagos airport for 6 months, starting next month.', ['Monthly volume' => '20 pallets'], EnquiryStatus::New],
            ['quote', 'Mei Lin', 'Shenzhen Solar Co.', 'mei@szsolar.example', 'Please quote 2 x 40ft containers of solar panels from Shenzhen to Apapa port, door delivery to Ibadan.', ['Monthly volume' => '2 containers'], EnquiryStatus::Contacted],
            ['business', 'Femi Alabi', 'Alabi & Sons', 'femi@alabi.example', 'We send about 50 parcels a week to the UK. Do you offer monthly invoicing and a discount?', ['Monthly volume' => '200 parcels'], EnquiryStatus::Won],
            ['general', 'Joy Eze', null, 'joy.eze@example.com', 'Can I send homemade food items like dried fish and pepper soup spice to London?', [], EnquiryStatus::New],
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
