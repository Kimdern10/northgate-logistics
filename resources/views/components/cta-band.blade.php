<section class="cta-band">
    <div class="container cta-inner" data-reveal>
        <div>
            <h2>Ready to ship?</h2>
            <p>Book online in two minutes. We'll be at your door on the date you choose.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn--primary btn--lg" href="{{ route('ship') }}" wire:navigate.hover>Book a pickup</a>
            <a class="btn btn--light btn--lg" href="{{ route('contact', ['type' => 'business']) }}" wire:navigate.hover>Open a business account</a>
        </div>
    </div>
</section>
