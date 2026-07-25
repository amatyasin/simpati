<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaCategoryResource\Pages;
use App\Models\MediaCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MediaCategoryResource extends Resource
{
    protected static ?string $model = MediaCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationGroup(): string
    {
        return 'Master Data';
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }

    protected static ?string $navigationLabel = 'Kategori Media';

    protected static ?string $modelLabel = 'Kategori Media';

    protected static ?string $pluralModelLabel = 'Kategori Media';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Kategori')
                ->icon('heroicon-o-tag')
                ->schema([


                    TextInput::make('name')
                        ->label('Nama Kategori')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Otomatis dari nama kategori.'),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Tampilan & Pengurutan')
                ->icon('heroicon-o-paint-brush')
                ->schema([
                    ColorPicker::make('color')
                        ->label('Warna'),

                    TextInput::make('icon')
                        ->label('Icon')
                        ->placeholder('heroicon-o-tag')
                        ->helperText('Nama heroicon, contoh: heroicon-o-document-text'),

                    TextInput::make('sort_order')
                        ->label('Urutan Tampil')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Warna'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->searchable(),



                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                TextColumn::make('media_count')
                    ->label('Jumlah Media')
                    ->counts('media')
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([


                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            ->defaultSort('sort_order');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Kategori')
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama'),

                    TextEntry::make('slug')
                        ->label('Slug')
                        ->badge()
                        ->color('gray'),



                    ColorEntry::make('color')
                        ->label('Warna'),

                    TextEntry::make('icon')
                        ->label('Icon'),

                    TextEntry::make('sort_order')
                        ->label('Urutan'),

                    TextEntry::make('description')
                        ->label('Deskripsi')
                        ->columnSpanFull(),

                    IconEntry::make('is_active')
                        ->label('Status Aktif')
                        ->boolean(),

                    TextEntry::make('created_at')
                        ->label('Dibuat Pada')
                        ->dateTime('d M Y H:i'),
                ])->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaCategories::route('/'),
            'create' => Pages\CreateMediaCategory::route('/create'),
            'view' => Pages\ViewMediaCategory::route('/{record}'),
            'edit' => Pages\EditMediaCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
