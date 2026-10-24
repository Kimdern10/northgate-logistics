<x-layouts::site title="Page not found">
    <section class="section not-found">
        <div class="container not-found-inner">
            <div class="lost-parcel" aria-hidden="true">
                <svg viewBox="0 0 200 160">
                    <ellipse class="lost-shadow" cx="100" cy="148" rx="54" ry="7"/>
                    <g class="lost-box">
                        <path d="M40 60 100 34l60 26v62l-60 26-60-26z" fill="var(--orange)"/>
                        <path d="M40 60l60 26 60-26M100 86v62" fill="none" stroke="rgba(0,0,0,.18)" stroke-width="3"/>
                        <path d="M70 47l60 26" stroke="#fff" stroke-opacity=".6" stroke-width="8"/>
                        <text x="100" y="128" text-anchor="middle" font-size="26" font-weight="800" fill="#fff" opacity=".9">?</text>
                    </g>
                </svg>
            </div>
            <p class="eyebrow">Error 404</p>
            <h1>This page got lost in transit.</h1>
            <p class="lead">The link may be old or mistyped. Let's get you back on route.</p>
            <div class="btn-row btn-row--center">
                <a class="btn btn--primary" href="{{ route('home') }}">Go to the home page</a>
                <a class="btn btn--ghost" href="{{ route('track') }}">Track a shipment</a>
            </div>
        </div>
    </section>
</x-layouts::site>
