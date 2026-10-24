<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'services' => Service::query()->published()->get(),
            'branches' => Branch::query()->published()->get(),
            'testimonials' => Testimonial::query()->published()->get(),
            'faqs' => Faq::query()->published()->get(),
        ]);
    }
}
