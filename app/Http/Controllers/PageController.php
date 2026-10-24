<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Faq;
use App\Models\Service;
use Illuminate\View\View;

class PageController extends Controller
{
    public function services(): View
    {
        return view('pages.services', [
            'services' => Service::query()->published()->get(),
            'faqs' => Faq::query()->published()->get(),
        ]);
    }

    public function service(Service $service): View
    {
        abort_unless($service->is_active, 404);

        return view('pages.service', [
            'service' => $service,
            'others' => Service::query()->published()->whereKeyNot($service->id)->get(),
        ]);
    }

    public function locations(): View
    {
        return view('pages.locations', [
            'branches' => Branch::query()->published()->get(),
        ]);
    }
}
