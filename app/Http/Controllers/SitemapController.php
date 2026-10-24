<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * List every public page for search engines.
     */
    public function __invoke(): Response
    {
        $pages = collect(['home', 'services.index', 'quote', 'ship', 'track', 'locations', 'about', 'contact', 'privacy'])
            ->map(fn (string $name): string => route($name));

        $services = Service::query()->published()->get(['id', 'slug', 'updated_at']);

        return response()
            ->view('sitemap', compact('pages', 'services'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Tell search engines what to crawl and where the sitemap is.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /dashboard',
            'Disallow: /settings',
            'Disallow: /waybill',
            'Disallow: /ship/booked',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
