<?php

namespace App\Enums;

enum AdPlacement: string
{
    case Header = 'header';
    case Sidebar = 'sidebar';
    case Footer = 'footer';
    case Popup = 'popup';
    case Banner = 'banner';
    case Inline = 'inline';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Header Ad',
            self::Sidebar => 'Sidebar Ad',
            self::Footer => 'Footer Ad',
            self::Popup => 'Popup Ad',
            self::Banner => 'Banner Ad',
            self::Inline => 'Inline Ad',
        };
    }

    public static function options(): array
    {
        return self::cases();
    }
}
