@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" class="brand-link">
<table class="brand" align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-logo"><img src="cid:{{ \App\Listeners\EmbedMailLogo::CID }}" class="logo" width="44" height="44" alt=""></td>
<td class="brand-name">{!! $slot !!}</td>
</tr>
</table>
</a>
</td>
</tr>
