<?php

namespace App\Filament\Resources\MediaResource\RelationManagers;

use App\Actions\RejectDocumentAction;
use App\Actions\RequestRevisionAction;
use App\Actions\VerifyDocumentAction;
use App\Enums\DocumentVerificationStatus;
use App\Models\MediaDocument;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MediaDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'mediaDocuments';

    protected static ?string $title = 'Dokumen Administratif';

    // =========================================================================
    // FORM
    // =========================================================================

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Data Dokumen')
                ->schema([
                    Select::make('document_type_id')
                        ->label('Tipe Dokumen')
                        ->relationship('documentType', 'name', fn ($query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($record) => $record !== null)
                        ->helperText('Tipe dokumen tidak dapat diubah setelah disimpan.'),

                    TextInput::make('document_number')
                        ->label('Nomor Dokumen / Surat')
                        ->required()
                        ->maxLength(255),

                    DatePicker::make('issue_date')
                        ->label('Tanggal Terbit')
                        ->native(false)
                        ->required()
                        ->maxDate(now()),

                    DatePicker::make('expiration_date')
                        ->label('Tanggal Kedaluwarsa')
                        ->native(false)
                        ->minDate(fn ($get) => $get('issue_date'))
                        ->helperText('Kosongkan jika dokumen berlaku selamanya.'),

                    SpatieMediaLibraryFileUpload::make('document_file')
                        ->label('File Dokumen')
                        ->collection('documents')
                        ->required(fn ($record) => $record === null)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ])
                        ->maxSize(10240)
                        ->helperText('Format: PDF, JPG, PNG. Maks. 10 MB.')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    // =========================================================================
    // TABLE
    // =========================================================================

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                TextColumn::make('documentType.name')
                    ->label('Tipe Dokumen')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Tgl Terbit')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label('Tgl Kedaluwarsa')
                    ->date('d M Y')
                    ->color(fn (MediaDocument $record) => match (true) {
                        $record->is_expired => 'danger',
                        $record->is_expiring_soon => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (MediaDocument $record) => match (true) {
                        $record->is_expired => '⚠️ Kedaluwarsa',
                        $record->is_expiring_soon => '🔔 Segera Kedaluwarsa',
                        default => '',
                    })
                    ->sortable(),

                TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'success',
                        DocumentVerificationStatus::PENDING => 'warning',
                        DocumentVerificationStatus::REVISION => 'info',
                        DocumentVerificationStatus::REJECTED => 'danger',
                    })
                    ->formatStateUsing(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'Disetujui ✅',
                        DocumentVerificationStatus::PENDING => 'Menunggu ⏳',
                        DocumentVerificationStatus::REVISION => 'Revisi ✏️',
                        DocumentVerificationStatus::REJECTED => 'Ditolak ❌',
                    })
                    ->sortable(),

                TextColumn::make('verification_notes')
                    ->label('Catatan')
                    ->wrap()
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('verifier.name')
                    ->label('Verifikator')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('verified_at')
                    ->label('Tgl Diverifikasi')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('verification_status')
                    ->label('Status')
                    ->options(DocumentVerificationStatus::class),

                SelectFilter::make('document_type_id')
                    ->label('Tipe Dokumen')
                    ->relationship('documentType', 'name'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Unggah Dokumen Baru')
                    ->modalWidth('xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['verification_status'] = DocumentVerificationStatus::PENDING->value;
                        $data['verifier_id'] = null;
                        $data['verification_notes'] = null;

                        return $data;
                    })
                    ->after(function (MediaDocument $record): void {
                        // Notify admins of new upload
                        app(NotificationService::class)
                            ->notifyAdminsOfNewDocument($record);
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalWidth('xl')
                    ->infolist(fn (Schema $schema) => $schema->schema([
                        Section::make('Detail Dokumen')
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('documentType.name')->label('Tipe Dokumen')->badge()->color('primary'),
                                TextEntry::make('document_number')->label('Nomor Dokumen'),
                                TextEntry::make('issue_date')->label('Tanggal Terbit')->date('d M Y'),
                                TextEntry::make('expiration_date')->label('Tanggal Kedaluwarsa')->date('d M Y'),
                                TextEntry::make('verification_status')
                                    ->label('Status Verifikasi')
                                    ->badge()
                                    ->color(fn (DocumentVerificationStatus $state) => match ($state) {
                                        DocumentVerificationStatus::APPROVED => 'success',
                                        DocumentVerificationStatus::PENDING => 'warning',
                                        DocumentVerificationStatus::REVISION => 'info',
                                        DocumentVerificationStatus::REJECTED => 'danger',
                                    }),
                                TextEntry::make('verification_notes')->label('Catatan Verifikasi')->columnSpanFull(),
                                TextEntry::make('verifier.name')->label('Verifikator'),
                                TextEntry::make('verified_at')->label('Diverifikasi Pada')->dateTime('d M Y H:i'),
                            ])->columns(2),
                    ])),

                EditAction::make()
                    ->label('Edit')
                    ->modalWidth('xl')
                    ->visible(fn (MediaDocument $record) => in_array(
                        $record->verification_status?->value,
                        ['pending', 'revision'],
                        true
                    ) || auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']))
                    ->mutateFormDataUsing(function (array $data, MediaDocument $record): array {
                        // Media partner re-submission resets status to pending
                        if (auth()->user()?->hasRole('media_partner')) {
                            $data['verification_status'] = DocumentVerificationStatus::PENDING->value;
                            $data['verification_notes'] = null;
                        }

                        return $data;
                    }),

                // -----------------------------------------------------------
                // Verify Action (Admin only)
                // -----------------------------------------------------------
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']))
                    ->form([
                        Select::make('decision')
                            ->label('Keputusan Verifikasi')
                            ->options([
                                'approved' => '✅ Setujui Dokumen',
                                'revision' => '✏️ Minta Revisi',
                                'rejected' => '❌ Tolak Dokumen',
                            ])
                            ->required()
                            ->live(),

                        Textarea::make('notes')
                            ->label('Catatan Verifikasi')
                            ->rows(3)
                            ->required(fn ($get) => in_array($get('decision'), ['revision', 'rejected'])),
                    ])
                    ->action(function (MediaDocument $record, array $data): void {
                        $verifierId = auth()->id();

                        match ($data['decision']) {
                            'approved' => app(VerifyDocumentAction::class)->execute($record, $verifierId, $data['notes'] ?? null),
                            'rejected' => app(RejectDocumentAction::class)->execute($record, $verifierId, $data['notes']),
                            'revision' => app(RequestRevisionAction::class)->execute($record, $verifierId, $data['notes']),
                        };

                        Notification::make()
                            ->title('Keputusan verifikasi berhasil disimpan.')
                            ->success()
                            ->send();
                    }),

                // -----------------------------------------------------------
                // Download Action
                // -----------------------------------------------------------
                Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (MediaDocument $record) => $record->getFirstMediaUrl('documents'))
                    ->openUrlInNewTab()
                    ->visible(fn (MediaDocument $record) => $record->getFirstMediaUrl('documents') !== ''),

                DeleteAction::make()
                    ->visible(fn (MediaDocument $record) => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin'])
                        || in_array($record->verification_status?->value, ['pending', 'revision'], true)),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
