<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Reporter = 'reporter';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Reporter => 'Reporter',
        };
    }

    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => [
                'add_news' => true,
                'delete_news' => true,
                'publish_news' => true,
                'manage_ads' => true,
                'manage_categories' => true,
                'manage_media' => true,
                'manage_staff' => true,
                'approve_news' => true,
            ],
            self::Editor => [
                'add_news' => true,
                'delete_news' => false,
                'publish_news' => true,
                'manage_ads' => true,
                'manage_categories' => true,
                'manage_media' => true,
                'manage_staff' => false,
                'approve_news' => true,
            ],
            self::Reporter => [
                'add_news' => true,
                'delete_news' => false,
                'publish_news' => false,
                'manage_ads' => false,
                'manage_categories' => false,
                'manage_media' => true,
                'manage_staff' => false,
                'approve_news' => false,
            ],
        };
    }
}
