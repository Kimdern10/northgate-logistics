<x-mail::message>
@if ($isShipment)
# New pickup booking

**{{ $request->tracking_number }}** · {{ $request->service_level->label() }}

- Sender: {{ $request->sender_name }}, {{ $request->sender_phone }}
- Pickup: {{ $request->sender_address }}, {{ $request->origin() }}{{ $request->pickup_date ? ' on '.$request->pickup_date->format('D j M Y') : '' }}
- To: {{ $request->receiver_name }}, {{ $request->destination() }}
- Contents: {{ $request->contents }} ({{ $request->pieces }} {{ str('piece')->plural($request->pieces) }}, {{ $request->weight_kg }} kg)
@else
# New enquiry: {{ $request->typeLabel() }}

- Name: {{ $request->name }}{{ $request->company ? ' ('.$request->company.')' : '' }}
- Email: {{ $request->email }}
@if ($request->phone)
- Phone: {{ $request->phone }}
@endif
@foreach ($request->details ?? [] as $label => $value)
- {{ $label }}: {{ $value }}
@endforeach

{{ $request->message }}
@endif

<x-mail::button :url="$adminUrl">
Open in the admin
</x-mail::button>
</x-mail::message>
