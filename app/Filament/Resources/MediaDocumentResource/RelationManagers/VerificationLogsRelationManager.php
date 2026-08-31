<?php

namespace App\Filament\Resources\MediaDocumentResource\RelationManagers;

use App\Enums\DocumentVerificationStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VerificationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'verificationLogs';

    protected static ?string $title = 'Riwayat Verifikasi';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label('Keputusan')
                    ->badge()
                    ->color(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'success',
                        DocumentVerificationStatus::PENDING => 'warning',
                        DocumentVerificationStatus::REVISION => 'info',
                        DocumentVerificationStatus::REJECTED => 'danger',
                    })
                    ->formatStateUsing(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'Disetujui',
                        DocumentVerificationStatus::PENDING => 'Menunggu',
                        DocumentVerificationStatus::REVISION => 'Revisi',
                        DocumentVerificationStatus::REJECTED => 'Ditolak',
                    }),

                TextColumn::make('notes')
                    ->label('Catatan Verifikator')
                    ->wrap()
                    ->default('—'),

                TextColumn::make('user.name')
                    ->label('Verifikator')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
