<?php

namespace App\Filament\Resources;

use App\Enums\MediaPriceStatus;
use App\Filament\Resources\MediaPriceResource\Pages;
use App\Models\MediaPrice;
use App\Models\MediaPriceUnit;
use App\Models\MediaServiceType;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MediaPriceResource extends Resource
{
    protected static ?string $model = MediaPrice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationGroup(): string
    {
        return 'Master Data';
    }

    protected static ?string $navigationLabel = 'Harga Media';

    protected static ?string $modelLabel = 'Harga Media';

    protected static ?string $pluralModelLabel = 'Harga Media';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        if (auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            $count = MediaPrice::pending()->count();

            return $count ? (string) $count : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        $isMediaPartner = auth()->user()?->hasRole('media_partner');

        return $schema->schema([
            Section::make('Informasi Harga & Media')
                ->columnSpanFull()
                ->schema([
                    Select::make('media_id')
                        ->label('Mitra Media')
                        ->relationship('media', 'brand_name', function (Builder $query) use ($isMediaPartner) {
                            if ($isMediaPartner) {
                                $query->where('user_id', auth()->id());
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($record) => $record !== null)
                        ->helperText('Pilih media partner yang akan diatur harganya.'),

                    Select::make('service_type')
                        ->label('Jenis Layanan / Publikasi')
                        ->options(function () {
                            $types = MediaServiceType::where('is_active', true)->pluck('name', 'name')->toArray();

                            return ! empty($types) ? $types : [
                                'Berita/Artikel' => 'Berita/Artikel',
                                'Banner' => 'Banner',
                                'Video' => 'Video',
                                'Sosial Media' => 'Sosial Media',
                                'Publikasi Lainnya' => 'Publikasi Lainnya',
                            ];
                        })
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nama Jenis Layanan Baru')
                                ->placeholder('Contoh: Radio/Audio, Infografis, Press Release...')
                                ->required()
                                ->maxLength(60),
                        ])
                        ->createOptionUsing(function (array $data): string {
                            $serviceType = MediaServiceType::create([
                                'name' => $data['name'],
                                'slug' => Str::slug($data['name']),
                                'is_active' => true,
                            ]);

                            return $serviceType->name;
                        })
                        ->createOptionAction(fn ($action) => $action->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin'])))
                        ->required(),

                    TextInput::make('price')
                        ->label('Harga (Rupiah)')
                        ->prefix('Rp')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->helperText('Masukkan nominal angka saja tanpa titik atau koma.'),

                    Select::make('unit')
                        ->label('Satuan')
                        ->options(function () {
                            $units = MediaPriceUnit::where('is_active', true)->pluck('name', 'name')->toArray();

                            return ! empty($units) ? $units : [
                                'Per Publikasi' => 'Per Publikasi',
                                'Per Hari' => 'Per Hari',
                                'Per Tayang' => 'Per Tayang',
                                'Per Artikel' => 'Per Artikel',
                                'Per Konten' => 'Per Konten',
                            ];
                        })
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nama Satuan Baru')
                                ->placeholder('Contoh: Per Eksplisit, Per Jam, Per Slot...')
                                ->required()
                                ->maxLength(50),
                        ])
                        ->createOptionUsing(function (array $data): string {
                            $unit = MediaPriceUnit::create([
                                'name' => $data['name'],
                                'slug' => Str::slug($data['name']),
                                'is_active' => true,
                            ]);

                            return $unit->name;
                        })
                        ->createOptionAction(fn ($action) => $action->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin'])))
                        ->required(),

                    Select::make('status')
                        ->label('Status Harga')
                        ->options(MediaPriceStatus::class)
                        ->default($isMediaPartner ? MediaPriceStatus::DRAFT->value : MediaPriceStatus::ACTIVE->value)
                        ->disabled($isMediaPartner)
                        ->dehydrated()
                        ->required(),

                    DatePicker::make('effective_from')
                        ->label('Berlaku Mulai')
                        ->native(false)
                        ->default(now())
                        ->required(),

                    DatePicker::make('effective_until')
                        ->label('Berlaku Sampai')
                        ->native(false)
                        ->minDate(fn ($get) => $get('effective_from'))
                        ->helperText('Kosongkan jika tidak ada batas tanggal berakhir.'),

                    Textarea::make('description')
                        ->label('Keterangan / Catatan Paket')
                        ->placeholder('Contoh: Paket berita tayang di halaman utama, include 1 foto & link sosial media...')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->disabled()
                        ->visible(fn ($record) => $record?->status === MediaPriceStatus::REJECTED)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('media.brand_name')
                    ->label('Mitra Media')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (MediaPrice $record) => $record->media?->company_name),

                TextColumn::make('service_type')
                    ->label('Jenis Layanan')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('formatted_price')
                    ->label('Harga')
                    ->weight('bold')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('price', $direction)),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('effective_from')
                    ->label('Berlaku Mulai')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('effective_until')
                    ->label('Berlaku Sampai')
                    ->date('d-m-Y')
                    ->placeholder('— (Tanpa Batas)')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (MediaPriceStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (MediaPriceStatus $state): string => $state->getLabel())
                    ->description(fn (MediaPrice $record) => $record->status === MediaPriceStatus::REJECTED ? "Alasan: {$record->rejection_reason}" : null)
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Harga')
                    ->options(MediaPriceStatus::class),

                SelectFilter::make('media_id')
                    ->label('Mitra Media')
                    ->relationship('media', 'brand_name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership'])),

                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        'Berita/Artikel' => 'Berita/Artikel',
                        'Banner' => 'Banner',
                        'Video' => 'Video',
                        'Sosial Media' => 'Sosial Media',
                        'Publikasi Lainnya' => 'Publikasi Lainnya',
                    ]),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make()->modalWidth('2xl'),

                // -----------------------------------------------------------
                // Submit Action (Media Partner or Admin)
                // -----------------------------------------------------------
                Action::make('submit')
                    ->label('Ajukan Persetujuan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (MediaPrice $record) => auth()->user()?->can('submit', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Persetujuan Harga')
                    ->modalDescription('Apakah Anda yakin ingin mengajukan harga ini untuk ditinjau oleh Admin Diskominfo?')
                    ->action(function (MediaPrice $record): void {
                        $record->submitForApproval();
                        Notification::make()
                            ->title('Harga Berhasil Diajukan')
                            ->body('Pengajuan harga sedang menunggu persetujuan Admin Diskominfo.')
                            ->success()
                            ->send();
                    }),

                // -----------------------------------------------------------
                // Approve Action (Admin only)
                // -----------------------------------------------------------
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MediaPrice $record) => auth()->user()?->can('approve', $record)
                        && in_array($record->status?->value, [MediaPriceStatus::PENDING->value, MediaPriceStatus::DRAFT->value], true))
                    ->requiresConfirmation()
                    ->action(function (MediaPrice $record): void {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('Harga Disetujui')
                            ->body('Harga media kini aktif dan dapat digunakan dalam transaksi.')
                            ->success()
                            ->send();
                    }),

                // -----------------------------------------------------------
                // Reject Action (Admin only)
                // -----------------------------------------------------------
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MediaPrice $record) => auth()->user()?->can('reject', $record)
                        && in_array($record->status?->value, [MediaPriceStatus::PENDING->value, MediaPriceStatus::DRAFT->value], true))
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->placeholder('Berikan alasan penolakan agar Media Partner dapat merevisi...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (MediaPrice $record, array $data): void {
                        $record->reject(auth()->id(), $data['rejection_reason']);
                        Notification::make()
                            ->title('Pengajuan Harga Ditolak')
                            ->body('Catatan penolakan telah dikirimkan ke Media Partner.')
                            ->warning()
                            ->send();
                    }),

                EditAction::make()->modalWidth('2xl'),
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
            ->defaultSort('effective_from', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Harga Media')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('media.brand_name')->label('Nama Media')->weight('bold'),
                    TextEntry::make('media.company_name')->label('Nama Perusahaan'),
                    TextEntry::make('media.user.name')->label('Media Partner (Pemilik Akun)')->placeholder('—'),
                    TextEntry::make('service_type')->label('Jenis Layanan')->badge()->color('primary'),
                    TextEntry::make('formatted_price')->label('Harga Nominal')->weight('bold'),
                    TextEntry::make('unit')->label('Satuan'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (MediaPriceStatus $state) => $state->getColor())
                        ->formatStateUsing(fn (MediaPriceStatus $state) => $state->getLabel()),
                    TextEntry::make('effective_from')->label('Berlaku Mulai')->date('d M Y'),
                    TextEntry::make('effective_until')->label('Berlaku Sampai')->date('d M Y')->placeholder('— (Tanpa Batas)'),
                    TextEntry::make('submitted_at')->label('Tanggal Diajukan')->dateTime('d M Y H:i')->placeholder('— (Belum Diajukan)'),
                    TextEntry::make('description')->label('Keterangan / Catatan Paket')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('rejection_reason')->label('Alasan Penolakan')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('createdBy.name')->label('Dibuat Oleh')->placeholder('—'),
                    TextEntry::make('updatedBy.name')->label('Diubah Oleh')->placeholder('—'),
                    TextEntry::make('approvedBy.name')->label('Disetujui Oleh')->placeholder('—'),
                    TextEntry::make('approved_at')->label('Disetujui Pada')->dateTime('d M Y H:i')->placeholder('—'),
                    TextEntry::make('rejectedBy.name')->label('Ditolak Oleh')->placeholder('—'),
                    TextEntry::make('rejected_at')->label('Ditolak Pada')->dateTime('d M Y H:i')->placeholder('—'),
                ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaPrices::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check() && auth()->user()->hasRole('media_partner')) {
            $query->whereHas('media', fn ($q) => $q->where('user_id', auth()->id()));
        }

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
