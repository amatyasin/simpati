<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public string $contact_email;

    public string $contact_phone;

    public string $address;

    public bool $maintenance_mode;

    public ?string $maintenance_message;

    public static function group(): string
    {
        return 'general';
    }
}
