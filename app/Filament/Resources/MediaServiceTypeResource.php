<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaServiceTypeResource\Pages;
use App\Models\MediaServiceType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MediaServiceTypeResource extends Resource
{
    protected static ?string $model = MediaServiceType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): string
    {
        return 'Master Data';
    }

    protected static ?string $navigationLabel = 'Jenis Layanan Media';

    protected static ?string $modelLabel = 'Jenis Layanan Media';

    protected static ?string $pluralModelLabel = 'Jenis Layanan Media';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Jenis Layanan / Publikasi')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Jenis Layanan')
                        ->placeholder('Contoh: Berita/Artikel, Banner, Video, Radio/Audio...')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(60)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->maxLength(70),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Jenis layanan aktif dapat dipilih oleh Media Partner dan Admin saat menentukan harga.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Jenis Layanan')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make()->modalWidth('lg'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaServiceTypes::route('/'),
        ];
    }
}
