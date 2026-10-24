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
                "For cargo from 45 kg to several tonnes, our air freight team books space with leading airlines and handles the airway bill, customs and delivery.\n\nChoose consolidated freight to save money, or a dedicated booking when the date matters most.",
                ['Airport-to-airport or door-to-door', 'Consolidated and dedicated options', 'Dangerous goods advice', 'Cargo insurance on request']],
            ['Sea Freight', 'ship', 'Full containers (FCL) and shared containers (LCL) between Africa, Europe, Asia and the Americas.',
                "Sea freight is the best value for heavy or bulky goods. Book a full 20ft or 40ft container, or share a container and pay only for the space you use.\n\nWe arrange port handling, customs clearance and final delivery by truck.",
                ['FCL 20ft and 40ft containers', 'LCL shared containers from 1 m³', 'Port-to-door delivery', 'Weekly sailings from China, UK and USA']],
            ['Customs Clearance', 'shield', 'Licensed customs brokers who prepare documents, pay duties for you and get goods released fast.',
                "Customs delays cost money. Our licensed brokers check your documents before the goods arrive, classify them correctly and pay duties on your behalf so cargo is released quickly.\n\nWe clear air, sea and road cargo at all major ports and airports.",
                ['Document check before arrival', 'Correct HS code classification', 'Duty and tax payment on your behalf', 'Import and export permits']],
            ['Warehousing', 'warehouse', 'Secure, insured storage with live stock levels, from a few shelves to whole pallet bays.',
                "Our warehouses are guarded 24/7, covered by CCTV and insured. Store goods short-term between shipments or long-term as your regional stock point.\n\nYou see your stock levels at any time, and we can receive, count, label and dispatch on your instructions.",
                ['24/7 security and CCTV', 'Pallet, shelf and bulk storage', 'Receiving, counting and labelling', 'Bonded storage for goods awaiting duty', 'Monthly stock reports']],
            ['E-commerce Fulfilment', 'cart', 'We store your products, pack every order and deliver to your customers, same day if they order by noon.',
                "Focus on selling while we do the rest. Send your stock to our fulfilment centre and connect your online shop. When a customer orders, we pick, pack and ship it, usually the same day.\n\nWe also handle returns, so your customers get a smooth experience from checkout to doorstep.",
                ['Same-day dispatch for orders by 12 noon', 'Branded packaging and inserts', 'Cash on delivery collection', 'Returns handling', 'Works with Shopify, WooCommerce and marketplaces']],
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
            ['Lagos Airport Hub', 'Lagos', 'NG', '12 Airport Road, Ikeja', '+234 1 700 0100', 6.5774, 3.3212, true, true],
            ['Lagos Island Branch', 'Lagos', 'NG', '45 Marina Street, Lagos Island', '+234 1 700 0101', 6.4541, 3.3947, false, false],
            ['Abuja Branch', 'Abuja', 'NG', 'Plot 220 Aminu Kano Crescent, Wuse 2', '+234 9 700 0200', 9.0765, 7.4729, false, false],
            ['Port Harcourt Branch', 'Port Harcourt', 'NG', '8 Aba Road, GRA Phase 2', '+234 84 700 300', 4.8156, 7.0498, false, true],
            ['Accra Branch', 'Accra', 'GH', '14 Liberation Road, Airport City', '+233 30 700 4000', 5.6037, -0.1870, false, false],
            ['London Heathrow Hub', 'London', 'GB', 'Unit 4, Bath Road, Hounslow', '+44 20 7000 5000', 51.4775, -0.4614, true, true],
            ['Houston Branch', 'Houston', 'US', '9800 Airline Drive', '+1 713 700 6000', 29.9902, -95.3368, false, true],
            ['Dubai Hub', 'Dubai', 'AE', 'Dubai Logistics City, Warehouse 12', '+971 4 700 7000', 24.8963, 55.1615, true, true],
            ['Guangzhou Branch', 'Guangzhou', 'CN', '88 Jichang Road, Baiyun District', '+86 20 7000 8000', 23.3924, 113.2988, false, true],
            ['Johannesburg Branch', 'Johannesburg', 'ZA', '22 Jones Road, Kempton Park', '+27 11 700 9000', -26.1367, 28.2411, false, false],
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
                Zone::Regional->value => [30, 1.8, 10, 20],
                Zone::International->value => [45, 2.2, 25, 45],
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
            ['Adaeze Okafor', 'Founder, Ada Fabrics', 'We ship fabric orders to customers in London and Houston every week. Pickup is always on time and my customers love the tracking emails.'],
            ['Tunde Bakare', 'Importer, Bakare Electronics', 'They cleared our container from Guangzhou in two days. No stories, no hidden charges. That is rare.'],
            ['Grace Mensah', 'Online seller, Accra', 'Moving my stock into their fulfilment centre was the best decision. Orders go out the same day and returns are handled for me.'],
            ['James Whitfield', 'Operations Manager, Brightline UK', 'Reliable air freight out of Heathrow with a team that actually answers the phone. Highly recommended.'],
            ['Fatima Bello', 'Student, Abuja', 'I sent documents to my university in Canada and they arrived in three days. The waybill and tracking made it stress free.'],
            ['Chinedu Eze', 'CEO, Kora Beauty', 'Their warehouse in Lagos holds all our stock and the monthly reports are spot on. A true logistics partner.'],
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
            ['How does e-commerce fulfilment work?', 'You send your stock to our fulfilment centre. When a customer orders from your shop, we pick, pack and deliver it, usually the same day, and handle any returns.'],
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
