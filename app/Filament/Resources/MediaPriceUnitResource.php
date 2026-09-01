<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaPriceUnitResource\Pages;
use App\Models\MediaPriceUnit;
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

class MediaPriceUnitResource extends Resource
{
    protected static ?string $model = MediaPriceUnit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    public static function getNavigationGroup(): string
    {
        return 'Master Data';
    }

    protected static ?string $navigationLabel = 'Satuan Harga';

    protected static ?string $modelLabel = 'Satuan Harga Media';

    protected static ?string $pluralModelLabel = 'Satuan Harga Media';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Satuan Harga')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Satuan')
                        ->placeholder('Contoh: Per Publikasi, Per Hari, Per Paket...')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->maxLength(60),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Satuan aktif dapat dipilih oleh Media Partner dan Admin saat membuat harga.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Satuan')
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
            'index' => Pages\ListMediaPriceUnits::route('/'),
        ];
    }
}
