<x-mail::message>
# Verify your email

Hi {{ $name }},

Enter this code on the {{ $site['business_name'] }} verification page to finish creating your account:

<div class="code-box">{{ $code }}</div>

The code works for **{{ $minutes }} minutes**. Never share it with anyone, our team will never ask you for it.

If you did not create an account, you can ignore this email.

Thanks,<br>
{{ $site['business_name'] }}
</x-mail::message>
