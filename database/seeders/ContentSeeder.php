<?php

namespace Database\Seeders;

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\Rate;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Website content and starting rates. Safe to run on the live server; the owner edits it all in the admin.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->services();
        $this->branches();
        $this->rates();
        $this->testimonials();
        $this->faqs();
    }

    private function services(): void
    {
        $services = [
            ['International Express', 'plane', 'Door-to-door air courier to 40+ countries, delivered in 2–5 working days.',
                "Our express service is the fastest way to send documents and parcels abroad. We collect from your door, fly your shipment on the next available flight and deliver to the receiver's door.\n\nEvery parcel is scanned at each step, so you and your receiver can follow it online and get an email at every update.",
                ['Pickup from your home or office', 'Priority handling and next-flight departure', 'Customs paperwork prepared for you', 'Email updates at every step', 'Proof of delivery with signature']],
            ['Air Freight', 'globe', 'Consolidated and dedicated air cargo for heavier shipments that still need to move fast.',
                "For cargo from 45 kg to several tons, our air freight team books space with leading airlines and handles the airway bill, customs and delivery.\n\nChoose consolidated freight to save money, or a dedicated booking when the date matters most.",
                ['Airport-to-airport or door-to-door', 'Consolidated and dedicated options', 'Dangerous goods advice', 'Cargo insurance on request']],
            ['Ocean Freight', 'ship', 'Full containers (FCL) and shared containers (LCL) from US ports to Europe, Asia, Africa and Latin America.',
                "Ocean freight is the best value for heavy or bulky goods. Book a full 20ft or 40ft container, or share a container and pay only for the space you use.\n\nWe sail from New York/New Jersey, Los Angeles/Long Beach, Savannah and Houston, and arrange port handling, customs clearance and final delivery by truck.",
                ['FCL 20ft and 40ft containers', 'LCL shared containers from 35 cu ft', 'Port-to-door delivery', 'Weekly sailings from East, West and Gulf Coast ports']],
            ['Customs Clearance', 'shield', 'Licensed customs brokers who prepare documents, pay duties for you and get goods released fast.',
                "Customs delays cost money. Our licensed brokers check your documents before the goods arrive, classify them correctly and pay duties on your behalf so cargo is released quickly.\n\nWe clear air, sea and road cargo at all major ports and airports.",
                ['Document check before arrival', 'Correct HS code classification', 'Duty and tax payment on your behalf', 'Import and export permits']],
            ['Warehousing', 'warehouse', 'Secure, insured storage with live stock levels, from a few shelves to whole pallet bays.',
                "Our warehouses are guarded 24/7, covered by CCTV and insured. Store goods short-term between shipments or long-term as your regional stock point.\n\nYou see your stock levels at any time, and we can receive, count, label and dispatch on your instructions.",
                ['24/7 security and CCTV', 'Pallet, shelf and bulk storage', 'Receiving, counting and labeling', 'Bonded storage for goods awaiting duty', 'Monthly stock reports']],
            ['E-commerce Fulfillment', 'cart', 'We store your products, pack every order and deliver to your customers, same day if they order by noon.',
                "Focus on selling while we do the rest. Send your stock to our fulfillment center and connect your online shop. When a customer orders, we pick, pack and ship it, usually the same day.\n\nWe also handle returns, so your customers get a smooth experience from checkout to doorstep.",
                ['Same-day dispatch for orders by 12 noon', 'Branded packaging and inserts', 'Cash on delivery (COD) collection', 'Returns handling', 'Works with Shopify, WooCommerce and marketplaces']],
        ];

        foreach ($services as $index => [$name, $icon, $summary, $description, $features]) {
            Service::query()->updateOrCreate(['slug' => str($name)->slug()->value()], [
                'name' => $name,
                'icon' => $icon,
                'summary' => $summary,
                'description' => $description,
                'features' => $features,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    private function branches(): void
    {
        $branches = [
            ['JFK Airport Hub', 'New York', 'US', '145-20 Rockaway Blvd, Jamaica, NY 11436', '+1 (718) 555-0110', 40.6620, -73.7880, true, true],
            ['Jersey City Head Office', 'Jersey City', 'US', '1200 Harbor Point Blvd, Suite 400, NJ 07310', '+1 (201) 555-0120', 40.7178, -74.0431, false, false],
            ['Los Angeles Hub', 'Los Angeles', 'US', '5800 W Century Blvd, Los Angeles, CA 90045', '+1 (310) 555-0130', 33.9456, -118.3870, true, true],
            ['Chicago Branch', 'Chicago', 'US', '2400 E Devon Ave, Elk Grove Village, IL 60007', '+1 (847) 555-0140', 41.9980, -87.9420, false, true],
            ['Miami Hub', 'Miami', 'US', '6500 NW 36th St, Miami, FL 33166', '+1 (305) 555-0150', 25.8090, -80.3080, true, true],
            ['Atlanta Branch', 'Atlanta', 'US', '3400 Interloop Rd, Atlanta, GA 30354', '+1 (404) 555-0160', 33.6460, -84.4120, false, false],
            ['Houston Branch', 'Houston', 'US', '9800 Airline Dr, Houston, TX 77037', '+1 (713) 555-0170', 29.9902, -95.3368, false, true],
            ['Dallas Fulfillment Center', 'Dallas', 'US', '4100 Valley View Ln, Irving, TX 75038', '+1 (972) 555-0180', 32.8690, -96.9390, false, true],
            ['London Heathrow Partner Hub', 'London', 'GB', 'Unit 4, Bath Road, Hounslow', '+44 20 7000 5000', 51.4775, -0.4614, true, false],
            ['Dubai Partner Hub', 'Dubai', 'AE', 'Dubai Logistics City, Warehouse 12', '+971 4 700 7000', 24.8963, 55.1615, true, false],
            ['Hong Kong Partner Hub', 'Hong Kong', 'HK', 'Chek Lap Kok Air Cargo Terminal', '+852 3700 8000', 22.3080, 113.9185, true, false],
        ];

        foreach ($branches as [$name, $city, $country, $address, $phone, $lat, $lng, $hub, $warehouse]) {
            Branch::query()->updateOrCreate(['name' => $name], [
                'city' => $city,
                'country' => $country,
                'address' => $address,
                'phone' => $phone,
                'email' => str($city)->slug()->value().'@northgate.example',
                'opening_hours' => $hub ? 'Open 24 hours, 7 days' : 'Mon–Fri 8am–6pm, Sat 9am–2pm',
                'latitude' => $lat,
                'longitude' => $lng,
                'is_hub' => $hub,
                'is_warehouse' => $warehouse,
                'is_active' => true,
            ]);
        }
    }

    private function rates(): void
    {
        // [base fee, per kg, min days, max days] in US dollars
        $rates = [
            ServiceLevel::Express->value => [
                Zone::Domestic->value => [8, 1.5, 1, 2],
                Zone::Regional->value => [25, 6, 2, 3],
                Zone::International->value => [35, 11, 2, 5],
            ],
            ServiceLevel::Standard->value => [
                Zone::Domestic->value => [5, 0.8, 2, 4],
                Zone::Regional->value => [15, 4, 4, 7],
                Zone::International->value => [20, 7, 5, 10],
            ],
            ServiceLevel::Economy->value => [
                Zone::Domestic->value => [4, 0.4, 3, 6],
                Zone::Regional->value => [12, 1.8, 10, 20],
                Zone::International->value => [15, 2.2, 25, 45],
            ],
        ];

        foreach ($rates as $level => $zones) {
            foreach ($zones as $zone => [$base, $perKg, $min, $max]) {
                Rate::query()->updateOrCreate(['service_level' => $level, 'zone' => $zone], [
                    'base_fee_cents' => (int) round($base * 100),
                    'per_kg_cents' => (int) round($perKg * 100),
                    'min_days' => $min,
                    'max_days' => $max,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function testimonials(): void
    {
        $testimonials = [
            ['Megan Carter', 'Founder, Carter & Co. Apparel, Brooklyn', 'We ship orders from New York to customers in London and Toronto every week. Pickup is always on time and my customers love the tracking emails.'],
            ['Daniel Reyes', 'Importer, Reyes Electronics, Miami', 'They cleared our container from Hong Kong in two days. No surprises, no hidden fees. That is rare.'],
            ['Priya Shah', 'Online seller, Austin TX', 'Moving my stock into their Dallas fulfillment center was the best decision. Orders go out the same day and returns are handled for me.'],
            ['Tom Whitfield', 'Operations Manager, Brightline Inc., Chicago', 'Reliable air freight out of O\'Hare with a team that actually answers the phone. Highly recommended.'],
            ['Ashley Nguyen', 'Graduate student, Los Angeles', 'I sent my documents to a university in Germany and they arrived in three days. The waybill and tracking made it stress free.'],
            ['Marcus Johnson', 'CEO, Lumen Beauty, Atlanta', 'Their warehouse holds all our stock and the monthly reports are spot on. A true logistics partner.'],
        ];

        foreach ($testimonials as $index => [$name, $role, $quote]) {
            Testimonial::query()->updateOrCreate(['name' => $name], [
                'role' => $role,
                'quote' => $quote,
                'rating' => 5,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    private function faqs(): void
    {
        $faqs = [
            ['How do I track my shipment?', 'Type your tracking number into the box on the home page or the Track page. You will see every update, with the place and time. We also email the sender and receiver at each step.'],
            ['How is the price worked out?', 'Price is a base fee plus a price per kilogram for the service and distance, plus a small fuel surcharge. Large, light boxes are charged by size (length × width × height ÷ 5000) if that is more than the real weight.'],
            ['Can you collect from my home or office?', 'Yes. Book a pickup online, choose a date and our courier will collect from your address. You can also drop parcels at any branch.'],
            ['What can I not send?', 'We cannot carry explosives, flammable liquids, weapons, illegal drugs, cash or live animals. Some countries also restrict food, medicines and batteries. Ask us if you are not sure.'],
            ['Who pays customs duties?', 'Unless agreed otherwise, the receiver pays any duties and taxes charged by the destination country. Our customs team can pay them for you and add them to your invoice.'],
            ['Do you offer insurance?', 'Yes. Tell us the value of your goods when booking and we can insure the shipment against loss or damage for a small percentage of the value.'],
            ['How does e-commerce fulfillment work?', 'You send your stock to our fulfillment center. When a customer orders from your shop, we pick, pack and deliver it, usually the same day, and handle any returns.'],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            Faq::query()->updateOrCreate(['question' => $question], [
                'answer' => $answer,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
