<?php

namespace App\Support;

class SocialShareUrls
{
    /**
     * Build platform share / compose URLs for the current page.
     *
     * @return array{facebook: string, twitter: string, whatsapp: string, instagram: string, youtube: string}
     */
    public static function build(string $url, string $title): array
    {
        $url = trim($url);
        $title = trim($title);
        $message = $title !== '' ? "{$title} {$url}" : $url;

        return [
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
            'twitter' => 'https://twitter.com/intent/tweet?url='.rawurlencode($url)
                .'&text='.rawurlencode($title),
            'whatsapp' => 'https://api.whatsapp.com/send?text='.rawurlencode($message),
            'instagram' => 'https://www.instagram.com/create/select/',
            'youtube' => 'https://www.youtube.com/upload',
        ];
    }
}
