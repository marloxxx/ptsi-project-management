<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PluginSettings extends Settings
{
    public bool $enable_activity_log = true;

    public bool $enable_media_library = true;

    public bool $enable_excel_export = true;

    public bool $enable_notifications = true;

    public bool $enable_two_factor_auth = true;

    public bool $enable_impersonation = true;

    public static function group(): string
    {
        return 'plugins';
    }
}
