<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ManageGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.manage-general-settings';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public ?array $data = [];

    public function mount(GeneralSettings $settings): void
    {
        $this->form->fill([
            'site_name' => $settings->site_name,
            'contact_email' => $settings->contact_email,
            'contact_phone' => $settings->contact_phone,
            'address' => $settings->address,
            'maintenance_mode' => $settings->maintenance_mode,
            'maintenance_message' => $settings->maintenance_message,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Instansi')
                    ->description('Kelola informasi dasar sistem SIMPATI.')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama Sistem/Instansi')
                            ->required(),
                        TextInput::make('contact_email')
                            ->label('Email Kontak')
                            ->email()
                            ->required(),
                        TextInput::make('contact_phone')
                            ->label('Nomor Telepon')
                            ->required(),
                        Textarea::make('address')
                            ->label('Alamat Instansi')
                            ->rows(3)
                            ->required(),
                    ])->columns(2),

                Section::make('Status Sistem')
                    ->description('Atur status ketersediaan sistem.')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Mode Perbaikan (Maintenance Mode)')
                            ->helperText('Aktifkan ini jika sistem sedang dalam perbaikan. Pengguna (kecuali Admin) tidak akan bisa login atau mengakses sistem.')
                            ->reactive(),
                        Textarea::make('maintenance_message')
                            ->label('Pesan Mode Perbaikan')
                            ->helperText('Pesan ini akan ditampilkan kepada pengguna saat mode perbaikan aktif.')
                            ->visible(fn (Get $get) => $get('maintenance_mode')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon('heroicon-o-check-circle')
                ->action('save')
                ->color('primary'),
        ];
    }

    public function save(GeneralSettings $settings): void
    {
        $data = $this->form->getState();

        $settings->site_name = $data['site_name'];
        $settings->contact_email = $data['contact_email'];
        $settings->contact_phone = $data['contact_phone'];
        $settings->address = $data['address'];
        $settings->maintenance_mode = $data['maintenance_mode'];
        $settings->maintenance_message = $data['maintenance_message'] ?? null;

        $settings->save();

        Notification::make()
            ->title('Berhasil disimpan')
            ->body('Pengaturan sistem telah diperbarui.')
            ->success()
            ->send();
    }
}
