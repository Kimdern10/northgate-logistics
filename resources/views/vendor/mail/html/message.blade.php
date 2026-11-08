@php($site = \App\Models\Setting::values())
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $site['business_name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
**{{ $site['business_name'] }}** · {{ $site['address'] }}

{{ $site['phone'] }} · [{{ $site['email'] }}](mailto:{{ $site['email'] }}) · [{{ str(config('app.url'))->after('://') }}]({{ config('app.url') }})

© {{ date('Y') }} {{ $site['business_name'] }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
