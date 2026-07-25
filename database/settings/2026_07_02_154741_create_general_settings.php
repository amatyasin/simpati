<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'SIMPATI Samarinda');
        $this->migrator->add('general.contact_email', 'diskominfo@samarindakota.go.id');
        $this->migrator->add('general.contact_phone', '0541-123456');
        $this->migrator->add('general.address', 'Jl. Kesuma Bangsa No. 84, Samarinda');
        $this->migrator->add('general.maintenance_mode', false);
        $this->migrator->add('general.maintenance_message', 'Sistem sedang dalam perbaikan rutin. Silakan kembali beberapa saat lagi.');
    }
};
