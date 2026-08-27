<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('subject_type')
                    ->label('Tipe Objek')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->toggleable(),

                TextColumn::make('subject_id')
                    ->label('ID Objek')
                    ->toggleable(),

                TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->default('System')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Waktu Kejadian')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Jenis Aksi')
                    ->options([
                        'created' => 'Created (Tambah)',
                        'updated' => 'Updated (Ubah)',
                        'deleted' => 'Deleted (Hapus)',
                    ]),

                SelectFilter::make('log_name')
                    ->label('Kategori Log')
                    ->options([
                        'media_partner'  => 'Mitra Media',
                        'media_document' => 'Dokumen Media',
                        'default'        => 'Default',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            InfolistSection::make('Informasi Utama Log')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    TextEntry::make('log_name')
                        ->label('Kategori Log')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('event')
                        ->label('Aksi')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default   => 'gray',
                        }),

                    TextEntry::make('causer.name')
                        ->label('Pelaku Tindakan')
                        ->default('Sistem / Otomatis')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('created_at')
                        ->label('Waktu Kejadian')
                        ->dateTime('d M Y H:i:s'),

                    TextEntry::make('subject_type')
                        ->label('Tipe Objek')
                        ->default('—'),

                    TextEntry::make('subject_id')
                        ->label('ID Objek')
                        ->default('—'),

                    TextEntry::make('description')
                        ->label('Deskripsi Kejadian')
                        ->columnSpan(2),
                ])->columns(4),

            InfolistSection::make('Detail Perubahan Data (Properties)')
                ->schema([
                    KeyValueEntry::make('properties.attributes')
                        ->label('Data Baru / Saat Ini')
                        ->columnSpan(fn ($record) => isset($record->properties['old']) ? 1 : 2),

                    KeyValueEntry::make('properties.old')
                        ->label('Data Lama (Sebelum Perubahan)')
                        ->visible(fn ($record) => isset($record->properties['old'])),
                ])->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view'  => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
