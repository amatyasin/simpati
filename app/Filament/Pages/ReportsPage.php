<?php

namespace App\Filament\Pages;

use App\Enums\DocumentVerificationStatus;
use App\Enums\MediaVerificationStatus;
use App\Exports\MediaExport;
use App\Exports\VerificationExport;
use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.reports-page';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Laporan & Ekspor Data';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function canAccess(): bool
    {
        return ! auth()->user()?->hasRole('media_partner');
    }

    public ?string $partnerStatus = 'all';

    public ?int $partnerCategoryId = null;

    // New Advanced Partner Filters
    public ?int $partnerMinScore = null;

    public ?int $partnerMaxScore = null;

    public ?int $partnerMinCompleteness = null;

    public ?int $partnerMaxCompleteness = null;

    public ?string $partnerStartDate = null;

    public ?string $partnerEndDate = null;

    public ?string $docStatus = 'all';

    public ?int $docTypeId = null;

    public ?string $docExpiration = 'all';

    // New Advanced Document Filters
    public ?string $docExpireStartDate = null;

    public ?string $docExpireEndDate = null;

    public function partnerFilterForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('partnerStatus')
                    ->label('Status Verifikasi')
                    ->options([
                        'all' => 'Semua Status',
                        'draft' => 'Draft',
                        'pending' => 'Menunggu Verifikasi',
                        'approved' => 'Terverifikasi',
                        'revision' => 'Butuh Revisi',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('all'),

                Select::make('partnerCategoryId')
                    ->label('Kategori Media')
                    ->options(MediaCategory::where('is_active', true)->pluck('name', 'id'))
                    ->placeholder('Semua Kategori'),

                TextInput::make('partnerMinScore')
                    ->label('Skor Verifikasi Minimal (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),

                TextInput::make('partnerMaxScore')
                    ->label('Skor Verifikasi Maksimal (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),

                TextInput::make('partnerMinCompleteness')
                    ->label('Kelengkapan Minimal (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),

                TextInput::make('partnerMaxCompleteness')
                    ->label('Kelengkapan Maksimal (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),

                DatePicker::make('partnerStartDate')
                    ->label('Tanggal Daftar Mulai')
                    ->native(false),

                DatePicker::make('partnerEndDate')
                    ->label('Tanggal Daftar Selesai')
                    ->native(false),
            ])
            ->columns(4)
            ->statePath('');
    }

    public function docFilterForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('docStatus')
                    ->label('Status Verifikasi')
                    ->options([
                        'all' => 'Semua Status',
                        'pending' => 'Menunggu Verifikasi',
                        'approved' => 'Disetujui',
                        'revision' => 'Butuh Revisi',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('all'),

                Select::make('docTypeId')
                    ->label('Tipe Dokumen')
                    ->options(DocumentType::where('is_active', true)->pluck('name', 'id'))
                    ->placeholder('Semua Tipe'),

                Select::make('docExpiration')
                    ->label('Status Kedaluwarsa')
                    ->options([
                        'all' => 'Semua',
                        'expired' => 'Sudah Kedaluwarsa',
                        'expiring_soon' => 'Segera Berakhir (≤ 30 hari)',
                    ])
                    ->default('all'),

                DatePicker::make('docExpireStartDate')
                    ->label('Masa Berlaku Mulai')
                    ->native(false),

                DatePicker::make('docExpireEndDate')
                    ->label('Masa Berlaku Selesai')
                    ->native(false),
            ])
            ->columns(3)
            ->statePath('');
    }

    protected function getForms(): array
    {
        return ['partnerFilterForm', 'docFilterForm'];
    }

    public function exportPartnersExcel(): BinaryFileResponse
    {
        $filename = 'laporan-mitra-media-'.now()->format('Ymd-His').'.xlsx';

        Notification::make()
            ->title('Mengekspor data mitra media…')
            ->info()
            ->send();

        return Excel::download(
            new MediaExport(
                $this->partnerStatus ?? 'all',
                $this->partnerCategoryId,
                $this->partnerMinScore,
                $this->partnerMaxScore,
                $this->partnerMinCompleteness,
                $this->partnerMaxCompleteness,
                $this->partnerStartDate,
                $this->partnerEndDate
            ),
            $filename
        );
    }

    public function exportDocumentsExcel(): BinaryFileResponse
    {
        $filename = 'laporan-dokumen-media-'.now()->format('Ymd-His').'.xlsx';

        Notification::make()
            ->title('Mengekspor data dokumen media…')
            ->info()
            ->send();

        return Excel::download(
            new VerificationExport(
                $this->docStatus ?? 'all',
                $this->docTypeId,
                $this->docExpireStartDate,
                $this->docExpireEndDate
            ),
            $filename
        );
    }

    public function getPartnerStats(): array
    {
        return [
            'total' => Media::count(),
            'approved' => Media::where('verification_status', MediaVerificationStatus::APPROVED->value)->count(),
            'pending' => Media::where('verification_status', MediaVerificationStatus::PENDING->value)->count(),
            'revision' => Media::where('verification_status', MediaVerificationStatus::REVISION->value)->count(),
        ];
    }

    public function getDocumentStats(): array
    {
        return [
            'total' => MediaDocument::count(),
            'pending' => MediaDocument::where('verification_status', DocumentVerificationStatus::PENDING->value)->count(),
            'approved' => MediaDocument::where('verification_status', DocumentVerificationStatus::APPROVED->value)->count(),
            'expired' => MediaDocument::where('expiration_date', '<', now())->count(),
            'expiring_soon' => MediaDocument::whereBetween('expiration_date', [now(), now()->addDays(30)])->count(),
        ];
    }
}
