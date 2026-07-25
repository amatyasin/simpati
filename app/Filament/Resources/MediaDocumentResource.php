<?php

namespace App\Filament\Resources;

use App\Actions\RejectDocumentAction;
use App\Actions\RequestRevisionAction;
use App\Actions\VerifyDocumentAction;
use App\Enums\DocumentVerificationStatus;
use App\Filament\Resources\MediaDocumentResource\Pages;
use App\Models\MediaDocument;
use App\Models\VerificationLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Get;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MediaDocumentResource extends Resource
{
    protected static ?string $model = MediaDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    protected static ?string $navigationLabel = 'Verification';

    protected static ?string $modelLabel = 'Dokumen Media';

    protected static ?string $pluralModelLabel = 'Dokumen Media';

    protected static ?int $navigationSort = 11;

    public static function getNavigationBadge(): ?string
    {
        return (string) MediaDocument::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    // =========================================================================
    // FORM
    // =========================================================================

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Dokumen')
                ->description('Detail metadata dokumen administratif media.')
                ->icon('heroicon-o-document')
                ->schema([
                    Select::make('media_id')
                        ->label('Mitra Media')
                        ->relationship('mediaPartner', 'brand_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn () => ! auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin'])),

                    Select::make('document_type_id')
                        ->label('Tipe Dokumen')
                        ->relationship('documentType', 'name', fn ($query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($record) => $record !== null)
                        ->unique(
                            table: 'media_documents',
                            column: 'document_type_id',
                            modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, Get $get, ?MediaDocument $record) {
                                return $rule->where('media_id', $get('media_id'))
                                    ->whereNull('deleted_at')
                                    ->ignore($record?->id);
                            }
                        ),

                    TextInput::make('document_number')
                        ->label('Nomor Dokumen')
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
                        ->helperText('Kosongkan jika dokumen tidak memiliki masa kedaluwarsa.'),

                    SpatieMediaLibraryFileUpload::make('document_file')
                        ->label('File Dokumen')
                        ->collection('documents')
                        ->required(fn ($record) => $record === null)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ])
                        ->maxSize(10240) // 10MB
                        ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): string {
                            $extension = $file->getClientOriginalExtension();
                            $baseName = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                            return "doc_" . time() . "_" . $baseName . "." . $extension;
                        })
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    // =========================================================================
    // TABLE
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mediaPartner.brand_name')
                    ->label('Mitra Media')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (MediaDocument $record) => $record->mediaPartner?->company_name),

                TextColumn::make('documentType.name')
                    ->label('Tipe Dokumen')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label('Kedaluwarsa')
                    ->date('d M Y')
                    ->color(fn (MediaDocument $record) => match (true) {
                        $record->is_expired       => 'danger',
                        $record->is_expiring_soon => 'warning',
                        default                   => 'gray',
                    })
                    ->description(fn (MediaDocument $record) => match (true) {
                        $record->is_expired       => '⚠️ Kedaluwarsa',
                        $record->is_expiring_soon => '🔔 Segera Kedaluwarsa',
                        default                   => 'Aktif',
                    })
                    ->sortable(),

                TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'success',
                        DocumentVerificationStatus::PENDING  => 'warning',
                        DocumentVerificationStatus::REVISION => 'info',
                        DocumentVerificationStatus::REJECTED => 'danger',
                    })
                    ->formatStateUsing(fn (DocumentVerificationStatus $state): string => match ($state) {
                        DocumentVerificationStatus::APPROVED => 'Disetujui ✅',
                        DocumentVerificationStatus::PENDING  => 'Menunggu ⏳',
                        DocumentVerificationStatus::REVISION => 'Revisi ✏️',
                        DocumentVerificationStatus::REJECTED => 'Ditolak ❌',
                    })
                    ->sortable(),

                TextColumn::make('verifier.name')
                    ->label('Verifikator')
                    ->default('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('media_id')
                    ->label('Mitra Media')
                    ->relationship('mediaPartner', 'brand_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('document_type_id')
                    ->label('Tipe Dokumen')
                    ->relationship('documentType', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('verification_status')
                    ->label('Status Verifikasi')
                    ->options(DocumentVerificationStatus::class),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (MediaDocument $record) => in_array(
                        $record->verification_status?->value,
                        ['pending', 'revision'],
                        true
                    ) || auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']))
                    ->mutateFormDataUsing(function (array $data): array {
                        if (auth()->user()?->hasRole('media_partner')) {
                            $data['verification_status'] = DocumentVerificationStatus::PENDING->value;
                            $data['verification_notes']  = null;
                        }

                        return $data;
                    }),

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
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =========================================================================
    // INFOLIST
    // =========================================================================

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Dokumen Administratif')
                ->schema([
                    TextEntry::make('mediaPartner.brand_name')
                        ->label('Mitra Media')
                        ->weight('bold'),

                    TextEntry::make('documentType.name')
                        ->label('Tipe Dokumen')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('document_number')
                        ->label('Nomor Dokumen'),

                    TextEntry::make('issue_date')
                        ->label('Tanggal Terbit')
                        ->date('d M Y')
                        ->placeholder('—'),

                    TextEntry::make('expiration_date')
                        ->label('Tanggal Kedaluwarsa')
                        ->date('d M Y')
                        ->placeholder('Seumur Hidup'),

                    TextEntry::make('verification_status')
                        ->label('Status Verifikasi')
                        ->badge()
                        ->color(fn (DocumentVerificationStatus $state): string => match ($state) {
                            DocumentVerificationStatus::APPROVED => 'success',
                            DocumentVerificationStatus::PENDING  => 'warning',
                            DocumentVerificationStatus::REVISION => 'info',
                            DocumentVerificationStatus::REJECTED => 'danger',
                        })
                        ->formatStateUsing(fn (DocumentVerificationStatus $state): string => match ($state) {
                            DocumentVerificationStatus::APPROVED => 'Disetujui',
                            DocumentVerificationStatus::PENDING  => 'Menunggu Verifikasi',
                            DocumentVerificationStatus::REVISION => 'Minta Revisi',
                            DocumentVerificationStatus::REJECTED => 'Ditolak',
                        }),

                    TextEntry::make('verifier.name')
                        ->label('Verifikator')
                        ->placeholder('—'),

                    TextEntry::make('verified_at')
                        ->label('Tanggal Diverifikasi')
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('verification_notes')
                        ->label('Catatan Verifikator')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ])->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\MediaDocumentResource\RelationManagers\VerificationLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMediaDocuments::route('/'),
            'create' => Pages\CreateMediaDocument::route('/create'),
            'view'   => Pages\ViewMediaDocument::route('/{record}'),
            'edit'   => Pages\EditMediaDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check() && auth()->user()->hasRole('media_partner')) {
            $query->whereHas('mediaPartner', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
