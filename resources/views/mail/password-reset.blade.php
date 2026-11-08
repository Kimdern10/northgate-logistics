<x-mail::message>
# Reset your password

Hi {{ $name }},

We received a request to reset the password for your {{ $site['business_name'] }} account. Click the button below to choose a new one.

<x-mail::button :url="$url">
Reset my password
</x-mail::button>

This link works for **{{ $minutes }} minutes**. If you did not ask to reset your password, you can ignore this email and your password stays the same.

Thanks,<br>
{{ $site['business_name'] }}

<x-slot:subcopy>
If the button does not work, copy this link into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
