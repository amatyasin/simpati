<?php

namespace App\Filament\Resources;

use App\Actions\RecalculateMediaScoreAction;
use App\Enums\MediaVerificationStatus;
use App\Exports\MediaExport;
use App\Filament\Resources\MediaResource\Pages;
use App\Filament\Resources\MediaResource\RelationManagers\MediaDocumentsRelationManager;
use App\Filament\Resources\MediaResource\RelationManagers\VerificationLogsRelationManager;
use App\Models\Media;
use App\Services\MediaScoreService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Media';

    protected static ?string $modelLabel = 'Mitra Media';

    protected static ?string $pluralModelLabel = 'Mitra Media';

    protected static ?string $recordTitleAttribute = 'brand_name';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Media::pending()->count() ?: null;
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
            Grid::make(3)->schema([
                // Main Content - 2/3 width
                Section::make('Profil & Identitas Media')
                    ->description('Informasi dasar identitas badan usaha media.')
                    ->icon('heroicon-o-newspaper')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('brand_name')
                            ->id('brand_name')
                            ->extraInputAttributes(['id' => 'brand_name'])
                            ->label('Nama Media / Brand')
                            ->placeholder('Tepian News')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('company_name')
                            ->id('company_name')
                            ->extraInputAttributes(['id' => 'company_name'])
                            ->label('Nama Perusahaan / Badan Hukum')
                            ->placeholder('PT Media Utama Mandiri')
                            ->required()
                            ->maxLength(255),

                        Select::make('user_id')
                            ->id('user_id')
                            ->extraInputAttributes(['id' => 'user_id'])
                            ->label('Akun Pemilik (Media Partner)')
                            ->relationship('user', 'name', fn ($query) => $query->whereHas(
                                'roles',
                                fn ($r) => $r->where('name', 'media_partner')
                            ))
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->id('new_user_name')
                                    ->extraInputAttributes(['id' => 'new_user_name'])
                                    ->label('Nama Lengkap')
                                    ->placeholder('Contoh: Budi Santoso')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->id('new_user_email')
                                    ->extraInputAttributes(['id' => 'new_user_email'])
                                    ->label('Email Login')
                                    ->placeholder('redaksi@media.com')
                                    ->email()
                                    ->required()
                                    ->unique('users', 'email')
                                    ->maxLength(255),
                                TextInput::make('password')
                                    ->id('new_user_password')
                                    ->extraInputAttributes(['id' => 'new_user_password'])
                                    ->label('Password')
                                    ->password()
                                    ->required()
                                    ->minLength(8),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $user = \App\Models\User::create([
                                    'name' => $data['name'],
                                    'email' => $data['email'],
                                    'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                                ]);
                                $user->assignRole('media_partner');
                                return $user->id;
                            })
                            ->disabled(fn () => ! auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']))
                            ->helperText('Akun yang memiliki dan mengelola profil ini. Klik (+) untuk buat akun baru.'),

                        Select::make('media_category_id')
                            ->id('media_category_id')
                            ->extraInputAttributes(['id' => 'media_category_id'])
                            ->label('Kategori Media')
                            ->relationship('mediaCategory', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('email')
                            ->id('email')
                            ->extraInputAttributes(['id' => 'email'])
                            ->label('Email Redaksi')
                            ->placeholder('redaksi@media.com')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->id('phone')
                            ->extraInputAttributes(['id' => 'phone'])
                            ->label('Nomor Telepon / WhatsApp')
                            ->placeholder('0812...')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('website')
                            ->id('website')
                            ->extraInputAttributes(['id' => 'website'])
                            ->label('Alamat Website')
                            ->placeholder('https://...')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('address')
                            ->id('address')
                            ->extraInputAttributes(['id' => 'address'])
                            ->label('Alamat Kantor')
                            ->placeholder('Alamat lengkap kantor redaksi...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                // Sidebar - 1/3 width
                Section::make('Logo & Identitas Visual')
                    ->icon('heroicon-o-photo')
                    ->columnSpan(1)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->id('logo')
                            ->extraInputAttributes(['id' => 'logo'])
                            ->label('Logo Media')
                            ->collection('logos')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1'])
                            ->maxSize(2048)
                            ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): string {
                                $extension = $file->getClientOriginalExtension();
                                $baseName = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                                return "logo_" . time() . "_" . $baseName . "." . $extension;
                            })
                            ->helperText('Format: JPG, PNG, WebP. Maks. 2 MB.'),
                    ]),
            ]),

            Section::make('Manajemen & Redaksi')
                ->description('Struktur kepemimpinan perusahaan.')
                ->icon('heroicon-o-users')
                ->schema([
                    TextInput::make('director')
                        ->id('director')
                        ->extraInputAttributes(['id' => 'director'])
                        ->label('Direktur / Pimpinan Perusahaan')
                        ->maxLength(255),

                    TextInput::make('chief_editor')
                        ->id('chief_editor')
                        ->extraInputAttributes(['id' => 'chief_editor'])
                        ->label('Pemimpin Redaksi')
                        ->maxLength(255),
                ])->columns(2),

            Section::make('Deskripsi Perusahaan')
                ->icon('heroicon-o-document-text')
                ->schema([
                    RichEditor::make('description')
                        ->id('description')
                        ->label('Profil Singkat Perusahaan')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'bulletList', 'orderedList', 'link',
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // =========================================================================
    // TABLE
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->label('Logo')
                    ->collection('logos')
                    ->circular()
                    ->defaultImageUrl(fn (Media $record) => $record->logo_url),

                TextColumn::make('brand_name')
                    ->label('Nama Media')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Media $record) => $record->company_name),

                TextColumn::make('mediaCategory.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('completeness_percentage')
                    ->label('Kelengkapan')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default      => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('verification_score')
                    ->label('Skor Verifikasi')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default      => 'danger',
                    })
                    ->sortable(),

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
                        MediaVerificationStatus::PENDING  => 'Menunggu Verifikasi',
                        MediaVerificationStatus::REVISION => 'Butuh Revisi',
                        MediaVerificationStatus::REJECTED => 'Ditolak',
                        MediaVerificationStatus::DRAFT    => 'Draft',
                    })
                    ->sortable(),

                TextColumn::make('mediaDocuments_count')
                    ->label('Dokumen')
                    ->counts('mediaDocuments')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label('Pemilik Akun')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('media_category_id')
                    ->label('Kategori Media')
                    ->relationship('mediaCategory', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('verification_status')
                    ->label('Status Verifikasi')
                    ->options(MediaVerificationStatus::class),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('merge_pdf')
                    ->label(fn (Media $record): string => $record->merged_pdf_url ? 'Generate Ulang PDF' : 'Gabungkan Dokumen PDF')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function (Media $record): void {
                        if ($record->mediaDocuments()->count() === 0) {
                            Notification::make()
                                ->title('Belum ada dokumen yang diunggah.')
                                ->body('Silakan unggah setidaknya 1 dokumen terlebih dahulu.')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            app(\App\Actions\MergeMediaDocumentsAction::class)->execute($record);
                            Notification::make()
                                ->title('PDF gabungan berhasil dibuat.')
                                ->body("Dokumen tersedia: {$record->available_documents_count} dari {$record->total_required_documents_count} dokumen wajib.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menggabungkan PDF')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_merged_pdf')
                    ->label('Lihat PDF')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn (Media $record): bool => $record->merged_pdf_url !== null)
                    ->url(fn (Media $record): string => route('media.merged-pdf.show', $record))
                    ->openUrlInNewTab(),

                Action::make('download_merged_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Media $record): bool => $record->merged_pdf_url !== null)
                    ->url(fn (Media $record): string => route('media.merged-pdf.download', $record)),

                Action::make('recalculate')
                    ->label('Hitung Ulang Skor')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']))
                    ->action(function (Media $record): void {
                        app(RecalculateMediaScoreAction::class)->execute($record);
                        Notification::make()
                            ->title('Skor berhasil dihitung ulang.')
                            ->success()
                            ->send();
                    }),

                Action::make('export_excel')
                    ->label('Ekspor Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership']))
                    ->action(fn (Media $record) => Excel::download(
                        new MediaExport('all'),
                        'mitra-media-' . now()->format('Ymd-His') . '.xlsx'
                    )),

                DeleteAction::make(),
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
            ->defaultSort('brand_name');
    }

    // =========================================================================
    // INFOLIST
    // =========================================================================

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            // Card 1: Identitas & Status Utama Media (Compact Header Grid)
            Section::make('Informasi & Status Mitra Media')
                ->icon('heroicon-o-newspaper')
                ->schema([
                    SpatieMediaLibraryImageEntry::make('logo')
                        ->label('Logo')
                        ->collection('logos')
                        ->circular()
                        ->defaultImageUrl(fn (Media $record) => $record->logo_url),

                    TextEntry::make('brand_name')
                        ->label('Nama Media / Brand')
                        ->size('lg')
                        ->weight('bold'),

                    TextEntry::make('company_name')
                        ->label('Nama Perusahaan (Badan Hukum)')
                        ->weight('semibold')
                        ->placeholder('—'),

                    TextEntry::make('mediaCategory.name')
                        ->label('Kategori Media')
                        ->badge()
                        ->color('primary')
                        ->placeholder('—'),

                    TextEntry::make('verification_status')
                        ->label('Status Verifikasi')
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
                            MediaVerificationStatus::PENDING  => 'Menunggu Verifikasi',
                            MediaVerificationStatus::REVISION => 'Butuh Revisi',
                            MediaVerificationStatus::REJECTED => 'Ditolak',
                            MediaVerificationStatus::DRAFT    => 'Draft',
                        }),

                    TextEntry::make('completeness_percentage')
                        ->label('Kelengkapan Dokumen')
                        ->formatStateUsing(fn ($state) => $state . '%')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state >= 80 => 'success',
                            $state >= 50 => 'warning',
                            default      => 'danger',
                        }),

                    TextEntry::make('verification_score')
                        ->label('Skor Verifikasi')
                        ->formatStateUsing(fn ($state) => $state . '%')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state >= 80 => 'success',
                            $state >= 50 => 'warning',
                            default      => 'danger',
                        }),

                    TextEntry::make('user.name')
                        ->label('Akun Pemilik')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('email')
                        ->label('Email Redaksi')
                        ->placeholder('—'),

                    TextEntry::make('phone')
                        ->label('Nomor Telepon / WA')
                        ->placeholder('—'),

                    TextEntry::make('director')
                        ->label('Direktur / Pimpinan')
                        ->placeholder('—'),

                    TextEntry::make('chief_editor')
                        ->label('Pemimpin Redaksi')
                        ->placeholder('—'),

                    TextEntry::make('website')
                        ->label('Alamat Website')
                        ->url(fn ($state) => $state)
                        ->openUrlInNewTab()
                        ->color('info')
                        ->placeholder('—')
                        ->columnSpan(2),

                    TextEntry::make('address')
                        ->label('Alamat Kantor')
                        ->placeholder('—')
                        ->columnSpan(2),
                ])->columns(4),

            // Card 2: Deskripsi & System Audit (Compact 2-Column Grid)
            Section::make('Profil Deskripsi & Status Dokumen')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('description')
                        ->label('Profil Singkat Perusahaan')
                        ->html()
                        ->placeholder('Belum ada deskripsi profil perusahaan.')
                        ->columnSpan(2),

                    IconEntry::make('has_expired_documents')
                        ->label('Dokumen Kedaluwarsa')
                        ->boolean()
                        ->trueIcon('heroicon-o-exclamation-triangle')
                        ->falseIcon('heroicon-o-check-circle')
                        ->trueColor('danger')
                        ->falseColor('success'),

                    IconEntry::make('has_expiring_soon_documents')
                        ->label('Segera Kedaluwarsa')
                        ->boolean()
                        ->trueIcon('heroicon-o-clock')
                        ->falseIcon('heroicon-o-check-circle')
                        ->trueColor('warning')
                        ->falseColor('success'),

                    TextEntry::make('created_at')
                        ->label('Terdaftar Pada')
                        ->dateTime('d M Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Diperbarui Pada')
                        ->dateTime('d M Y H:i'),
                ])->columns(4),
        ]);
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public static function getRelations(): array
    {
        return [
            MediaDocumentsRelationManager::class,
            VerificationLogsRelationManager::class,
        ];
    }

    // =========================================================================
    // PAGES
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'view'   => Pages\ViewMedia::route('/{record}'),
            'edit'   => Pages\EditMedia::route('/{record}/edit'),
        ];
    }

    // =========================================================================
    // QUERY SCOPE
    // =========================================================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check() && auth()->user()->hasRole('media_partner')) {
            $query->where('user_id', auth()->id());
        }

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
