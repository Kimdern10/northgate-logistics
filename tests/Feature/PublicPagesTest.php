<?php

use App\Models\Branch;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Testimonial;
use Database\Seeders\ContentSeeder;
use Livewire\Livewire;

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

test('the locations search filters branches live by city, country or type', function () {
    Branch::factory()->create(['name' => 'JFK Airport Hub', 'city' => 'New York', 'country' => 'US', 'is_hub' => true]);
    Branch::factory()->create(['name' => 'Dallas Fulfillment Center', 'city' => 'Dallas', 'country' => 'US', 'is_warehouse' => true]);
    Branch::factory()->create(['name' => 'London Partner Hub', 'city' => 'London', 'country' => 'GB', 'is_hub' => true]);

    // The map above the list always holds every branch, so check the result cards' headings.
    Livewire::test('pages::locations')
        ->assertSee('3 of 3 locations')
        ->set('search', 'united kingdom')
        ->assertSeeHtml('<h2>London Partner Hub</h2>')
        ->assertDontSeeHtml('<h2>JFK Airport Hub</h2>')
        ->set('search', 'dal')
        ->assertSeeHtml('<h2>Dallas Fulfillment Center</h2>')
        ->assertDontSeeHtml('<h2>London Partner Hub</h2>')
        ->set('search', '')
        ->set('type', 'hubs')
        ->assertSee('2 of 3 locations')
        ->assertSeeHtml('<h2>JFK Airport Hub</h2>')
        ->assertDontSeeHtml('<h2>Dallas Fulfillment Center</h2>')
        ->set('search', 'nowhere')
        ->assertSee('No locations match');
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
