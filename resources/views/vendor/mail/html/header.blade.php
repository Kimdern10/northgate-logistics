@props(['url'])
@php
    // Inboxes can only load the logo picture when the site is online on a public https address.
    // Before that (local testing), the logo is drawn with HTML so nothing shows up as an attachment.
    $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    $hostedLogo = str_starts_with((string) config('app.url'), 'https://')
        && ! preg_match('/(^localhost|^127\.|\.test$|\.local$|\.localhost$)/', $host);
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" class="brand-link">
<table class="brand" align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-logo">
@if ($hostedLogo)
<img src="{{ rtrim((string) config('app.url'), '/') }}/images/mail-logo.png" class="logo" width="44" height="44" alt="">
@else
<table class="logo-mark" cellpadding="0" cellspacing="0" role="presentation"><tr><td class="logo-mark-cell" width="44" height="44" align="center" valign="middle"><span class="logo-mark-m">M</span><span class="logo-mark-dot">&#9679;</span></td></tr></table>
@endif
</td>
<td class="brand-name">{!! $slot !!}</td>
</tr>
</table>
</a>
</td>
</tr>
