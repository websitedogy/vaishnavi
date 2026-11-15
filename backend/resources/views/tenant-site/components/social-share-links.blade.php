@php
    $shareUrl = $shareUrl ?? url()->current();
    $shareTitle = $shareTitle ?? ($tenant->name ?? '');
    $links = \App\Support\SocialShareUrls::build($shareUrl, $shareTitle);
    $variant = $variant ?? 'header';
@endphp
@if($variant === 'footer')
    <div class="ts-site-footer__social" aria-label="Share on social media">
        <a href="{{ $links['facebook'] }}" class="ts-site-footer__social-link ts-site-footer__social-link--fb" title="Share on Facebook" target="_blank" rel="noopener noreferrer">Facebook</a>
        <a href="{{ $links['twitter'] }}" class="ts-site-footer__social-link ts-site-footer__social-link--x" title="Share on X" target="_blank" rel="noopener noreferrer">X</a>
        <a href="{{ $links['youtube'] }}" class="ts-site-footer__social-link ts-site-footer__social-link--yt" title="Upload on YouTube" target="_blank" rel="noopener noreferrer">YouTube</a>
        <a href="{{ $links['whatsapp'] }}" class="ts-site-footer__social-link ts-site-footer__social-link--wa" title="Share on WhatsApp" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a href="{{ $links['instagram'] }}" class="ts-site-footer__social-link ts-site-footer__social-link--ig" title="Create post on Instagram" target="_blank" rel="noopener noreferrer">Instagram</a>
    </div>
@else
    <div class="ts-social" aria-label="Share on social media">
        <a href="{{ $links['facebook'] }}" class="ts-social__link ts-social__link--fb" title="Share on Facebook" target="_blank" rel="noopener noreferrer">f</a>
        <a href="{{ $links['twitter'] }}" class="ts-social__link ts-social__link--x" title="Share on X" target="_blank" rel="noopener noreferrer">𝕏</a>
        <a href="{{ $links['whatsapp'] }}" class="ts-social__link ts-social__link--wa" title="Share on WhatsApp" target="_blank" rel="noopener noreferrer">W</a>
        <a href="{{ $links['instagram'] }}" class="ts-social__link ts-social__link--ig" title="Create post on Instagram" target="_blank" rel="noopener noreferrer">◎</a>
    </div>
@endif
