<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('plugins.enable_activity_log', true);
        $this->migrator->add('plugins.enable_media_library', true);
        $this->migrator->add('plugins.enable_excel_export', true);
        $this->migrator->add('plugins.enable_notifications', true);
        $this->migrator->add('plugins.enable_two_factor_auth', true);
        $this->migrator->add('plugins.enable_impersonation', true);
    }
};
