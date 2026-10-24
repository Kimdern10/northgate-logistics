<?php

use App\Models\Branch;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Testimonial;
use Database\Seeders\ContentSeeder;

test('every public page opens', function (string $route) {
    $this->seed(ContentSeeder::class);

    $this->get(route($route))->assertOk();
})->with(['home', 'services.index', 'quote', 'ship', 'contact', 'track', 'locations', 'about', 'privacy']);

test('the home page shows the content the owner manages', function () {
    Service::factory()->create(['name' => 'Air Freight']);
    Service::factory()->create(['name' => 'Hidden Service', 'is_active' => false]);
    Testimonial::factory()->create(['quote' => 'Always on time.']);
    Faq::factory()->create(['question' => 'Do you ship to Canada?']);
    Branch::factory()->create(['name' => 'Lagos Airport Hub', 'is_hub' => true]);

    $this->get(route('home'))
        ->assertSee('Air Freight')
        ->assertDontSee('Hidden Service')
        ->assertSee('Always on time.')
        ->assertSee('Do you ship to Canada?')
        ->assertSee('Lagos Airport Hub')
        ->assertSee('"@type":"FAQPage"', false);
});

test('a hidden service page is not found', function () {
    $service = Service::factory()->create(['is_active' => false]);

    $this->get(route('services.show', $service))->assertNotFound();
});

test('the sitemap lists the pages and services', function () {
    $service = Service::factory()->create();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=UTF-8')
        ->assertSee(route('services.show', $service), false)
        ->assertSee(route('quote'), false);
});

test('robots.txt keeps search engines out of the admin and private links', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /waybill')
        ->assertSee('Sitemap: '.route('sitemap'));
});

test('a missing page shows the branded 404', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('This page got lost in transit.');
});
