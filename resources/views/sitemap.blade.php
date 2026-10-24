{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($pages as $page)
    <url><loc>{{ $page }}</loc><changefreq>weekly</changefreq><priority>{{ $loop->first ? '1.0' : '0.7' }}</priority></url>
@endforeach
@foreach ($services as $service)
    <url><loc>{{ route('services.show', $service) }}</loc>@if ($service->updated_at)<lastmod>{{ $service->updated_at->toAtomString() }}</lastmod>@endif<changefreq>monthly</changefreq><priority>0.8</priority></url>
@endforeach
</urlset>
