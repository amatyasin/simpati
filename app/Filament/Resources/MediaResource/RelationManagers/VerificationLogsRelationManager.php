<?php

namespace App\Filament\Resources\MediaResource\RelationManagers;

use App\Enums\DocumentVerificationStatus;
use App\Models\VerificationLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VerificationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'mediaDocuments';

    protected static ?string $title = 'Riwayat Verifikasi';

    /**
     * We override the query to gather logs from all documents of this media.
     */
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
            ->query(
                VerificationLog::query()
                    ->whereIn(
                        'media_document_id',
                        $this->getOwnerRecord()->mediaDocuments()->pluck('id')
                    )
                    ->with(['user', 'mediaDocument.documentType'])
                    ->latest()
            )
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('mediaDocument.documentType.name')
                    ->label('Tipe Dokumen')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('mediaDocument.document_number')
                    ->label('Nomor Dokumen'),

                TextColumn::make('status')
                    ->label('Keputusan')
                    ->badge()
                    ->color(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'success',
                        DocumentVerificationStatus::PENDING  => 'warning',
                        DocumentVerificationStatus::REVISION => 'info',
                        DocumentVerificationStatus::REJECTED => 'danger',
                    })
                    ->formatStateUsing(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'Disetujui',
                        DocumentVerificationStatus::PENDING  => 'Menunggu',
                        DocumentVerificationStatus::REVISION => 'Revisi',
                        DocumentVerificationStatus::REJECTED => 'Ditolak',
                    }),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->wrap()
                    ->limit(80),

                TextColumn::make('user.name')
                    ->label('Oleh')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
