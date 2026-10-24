@php($site = \App\Models\Setting::values())

<x-layouts::site current="privacy" title="Privacy policy" description="How {{ $site['business_name'] }} collects, uses and protects your personal information.">
    <section class="page-hero page-hero--small">
        <div class="container">
            <h1>Privacy policy</h1>
            <p class="lead">Last updated {{ now()->format('j F Y') }}</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container prose prose--narrow">
            <p>This policy explains what personal information {{ $site['business_name'] }} ("we") collects when you use this website or ship with us, and how we use it.</p>

            <h2>What we collect</h2>
            <ul>
                <li><strong>Shipment details:</strong> names, phone numbers, email addresses and addresses of the sender and receiver, and what the parcel contains.</li>
                <li><strong>Messages:</strong> what you send us through the contact form.</li>
                <li><strong>Technical data:</strong> your IP address and browser type, used to keep the site secure and stop abuse.</li>
            </ul>

            <h2>How we use it</h2>
            <ul>
                <li>To collect, ship and deliver your parcel, and to clear it through customs.</li>
                <li>To send you tracking updates by email.</li>
                <li>To answer your questions and send quotes you asked for.</li>
            </ul>
            <p>We do not sell your information. We share it only with the airlines, shipping lines, customs authorities and delivery partners who need it to move your shipment.</p>

            <h2>Tracking page</h2>
            <p>Anyone with a tracking number can see a shipment's progress, cities and dates. The public page does not show full names, phone numbers or street addresses.</p>

            <h2>How long we keep it</h2>
            <p>We keep shipment records for up to six years for tax and customs purposes, then delete them.</p>

            <h2>Cookies</h2>
            <p>We use only the cookies needed for the site to work (for example to keep forms secure). We remember your light or dark mode choice in your browser.</p>

            <h2>Your rights</h2>
            <p>You can ask to see, correct or delete your personal information. Email <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a> and we will reply within 30 days.</p>

            <h2>Contact</h2>
            <p>{{ $site['business_name'] }}, {{ $site['address'] }} · {{ $site['phone'] }}</p>
        </div>
    </section>
</x-layouts::site>
