<?php

namespace App\Filament\Widgets;

use App\Enums\MediaVerificationStatus;
use App\Models\Media;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentMediaWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Mitra Media Terbaru';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! auth()->user()?->hasRole('media_partner');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Media::query()
                    ->with(['mediaCategory', 'user'])
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('brand_name')
                    ->label('Nama Media')
                    ->weight('semibold')
                    ->description(fn (Media $record) => $record->company_name),

                TextColumn::make('mediaCategory.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('completeness_percentage')
                    ->label('Kelengkapan')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default      => 'danger',
                    }),

                TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (MediaVerificationStatus $state): string => match ($state) {
                        MediaVerificationStatus::APPROVED => 'success',
                        MediaVerificationStatus::PENDING  => 'warning',
                        MediaVerificationStatus::REVISION => 'info',
                        MediaVerificationStatus::REJECTED => 'danger',
                        default                           => 'gray',
                    })
                    ->formatStateUsing(fn (MediaVerificationStatus $state): string => match ($state) {
                        MediaVerificationStatus::APPROVED => 'Terverifikasi',
                        MediaVerificationStatus::PENDING  => 'Menunggu',
                        MediaVerificationStatus::REVISION => 'Revisi',
                        MediaVerificationStatus::REJECTED => 'Ditolak',
                        MediaVerificationStatus::DRAFT    => 'Draft',
                    }),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->since(),
            ])
            ->paginated(false);
    }
}
