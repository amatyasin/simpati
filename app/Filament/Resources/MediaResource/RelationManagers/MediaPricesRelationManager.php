<?php

namespace App\Filament\Resources\MediaResource\RelationManagers;

use App\Enums\MediaPriceStatus;
use App\Models\MediaPrice;
use App\Models\MediaPriceUnit;
use App\Models\MediaServiceType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Str;

class MediaPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'mediaPrices';

    protected static ?string $title = 'Harga Media';

    public function form(Schema $schema): Schema
    {
        $isMediaPartner = auth()->user()?->hasRole('media_partner');

        return $schema->schema([
            Section::make('Informasi Harga & Layanan')
                ->columnSpanFull()
                ->schema([
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
                        ->required()
                        ->helperText('Pilih jenis layanan publikasi.'),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('service_type')
            ->columns([
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
                    ->label('Ditambahkan Oleh')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Harga')
                    ->options(MediaPriceStatus::class),

                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        'Berita/Artikel' => 'Berita/Artikel',
                        'Banner' => 'Banner',
                        'Video' => 'Video',
                        'Sosial Media' => 'Sosial Media',
                        'Publikasi Lainnya' => 'Publikasi Lainnya',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('+ Tambah Harga')
                    ->modalWidth('2xl')
                    ->visible(fn () => auth()->user()?->can('create', MediaPrice::class))
                    ->mutateFormDataUsing(function (array $data): array {
                        if (auth()->user()?->hasRole('media_partner')) {
                            $data['status'] = MediaPriceStatus::DRAFT->value;
                        }

                        return $data;
                    })
                    ->before(function (CreateAction $action, array $data): void {
                        $mediaId = $this->getOwnerRecord()->id;
                        $serviceType = $data['service_type'];
                        $status = $data['status'] ?? 'draft';

                        if ($status === MediaPriceStatus::ACTIVE->value) {
                            $from = $data['effective_from'];
                            $until = $data['effective_until'] ?? null;

                            $overlap = MediaPrice::where('media_id', $mediaId)
                                ->where('service_type', $serviceType)
                                ->where('status', MediaPriceStatus::ACTIVE->value)
                                ->where(function ($q) use ($from, $until) {
                                    $q->where(function ($sub) use ($from, $until) {
                                        $sub->where('effective_from', '<=', $until ?? '9999-12-31')
                                            ->where(function ($q2) use ($from) {
                                                $q2->whereNull('effective_until')
                                                    ->orWhere('effective_until', '>=', $from);
                                            });
                                    });
                                })
                                ->exists();

                            if ($overlap) {
                                Notification::make()
                                    ->title('Gagal Menyimpan Harga')
                                    ->body("Sudah ada harga aktif untuk media dan jenis layanan '{$serviceType}' pada periode yang sama.")
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalWidth('2xl')
                    ->infolist(fn (Schema $schema) => $schema->schema([
                        Section::make('Detail Harga Media')
                            ->columnSpanFull()
                            ->schema([
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
                                TextEntry::make('description')->label('Keterangan / Catatan Paket')->placeholder('—')->columnSpanFull(),
                                TextEntry::make('rejection_reason')->label('Alasan Penolakan')->placeholder('—')->columnSpanFull(),
                                TextEntry::make('createdBy.name')->label('Dibuat Oleh')->placeholder('—'),
                                TextEntry::make('updatedBy.name')->label('Diubah Oleh')->placeholder('—'),
                                TextEntry::make('approvedBy.name')->label('Disetujui Oleh')->placeholder('—'),
                                TextEntry::make('approved_at')->label('Disetujui Pada')->dateTime('d M Y H:i')->placeholder('—'),
                                TextEntry::make('rejectedBy.name')->label('Ditolak Oleh')->placeholder('—'),
                                TextEntry::make('rejected_at')->label('Ditolak Pada')->dateTime('d M Y H:i')->placeholder('—'),
                            ])->columns(2),
                    ])),

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

                EditAction::make()
                    ->modalWidth('2xl')
                    ->visible(fn (MediaPrice $record) => auth()->user()?->can('update', $record))
                    ->before(function (EditAction $action, MediaPrice $record, array $data): void {
                        $status = $data['status'] ?? $record->status?->value;

                        if ($status === MediaPriceStatus::ACTIVE->value) {
                            $from = $data['effective_from'];
                            $until = $data['effective_until'] ?? null;

                            $overlap = MediaPrice::where('media_id', $record->media_id)
                                ->where('service_type', $data['service_type'])
                                ->where('status', MediaPriceStatus::ACTIVE->value)
                                ->where('id', '!=', $record->id)
                                ->where(function ($q) use ($from, $until) {
                                    $q->where('effective_from', '<=', $until ?? '9999-12-31')
                                        ->where(function ($q2) use ($from) {
                                            $q2->whereNull('effective_until')
                                                ->orWhere('effective_until', '>=', $from);
                                        });
                                })
                                ->exists();

                            if ($overlap) {
                                Notification::make()
                                    ->title('Gagal Memperbarui Harga')
                                    ->body("Sudah ada harga aktif untuk media dan jenis layanan '{$data['service_type']}' pada periode yang sama.")
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }
                    }),

                DeleteAction::make()
                    ->visible(fn (MediaPrice $record) => auth()->user()?->can('delete', $record)),
            ])
            ->defaultSort('effective_from', 'desc');
    }
}
