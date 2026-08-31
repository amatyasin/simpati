<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTypeResource\Pages;
use App\Models\DocumentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    public static function getNavigationGroup(): string
    {
        return 'Master Data';
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }

    protected static ?string $navigationLabel = 'Tipe Dokumen';

    protected static ?string $modelLabel = 'Tipe Dokumen';

    protected static ?string $pluralModelLabel = 'Tipe Dokumen';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Tipe Dokumen')
                ->icon('heroicon-o-document-duplicate')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Tipe')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('code')
                        ->label('Kode')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->dehydrateStateUsing(fn ($state) => $state ? strtoupper($state) : $state)
                        ->helperText('Contoh: PDF, VIDEO, IMAGE'),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Konfigurasi File & Bobot Scoring')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    TagsInput::make('allowed_extensions')
                        ->label('Ekstensi yang Diizinkan')
                        ->placeholder('Tambah ekstensi, tekan Enter')
                        ->helperText('Contoh: pdf, docx, xlsx')
                        ->columnSpanFull(),

                    TextInput::make('max_file_size_mb')
                        ->label('Ukuran Maksimal (MB)')
                        ->numeric()
                        ->suffix('MB')
                        ->minValue(1)
                        ->maxValue(10240),

                    TextInput::make('icon')
                        ->label('Icon')
                        ->placeholder('heroicon-o-document-text'),

                    TextInput::make('weight')
                        ->label('Bobot Nilai Scoring')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->helperText('Bobot nilai relatif dokumen ini terhadap penilaian total.'),

                    TextInput::make('validity_days')
                        ->label('Masa Berlaku Dokumen (Hari)')
                        ->numeric()
                        ->suffix('Hari')
                        ->minValue(1)
                        ->helperText('Kosongkan jika dokumen berlaku selamanya.'),

                    Toggle::make('is_required')
                        ->label('Wajib Diunggah')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger'),

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
                TextColumn::make('name')
                    ->label('Nama Tipe')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('allowed_extensions')
                    ->label('Ekstensi')
                    ->badge()
                    ->color('gray')
                    ->separator(',')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),

                TextColumn::make('max_file_size_mb')
                    ->label('Maks. Ukuran')
                    ->suffix(' MB')
                    ->sortable(),

                TextColumn::make('weight')
                    ->label('Bobot')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()->label('Total Bobot')),

                IconColumn::make('is_required')
                    ->label('Wajib')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('validity_days')
                    ->label('Masa Berlaku')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} Hari" : 'Selamanya')
                    ->sortable(),

                TextColumn::make('media_documents_count')
                    ->label('Jumlah Dokumen')
                    ->counts('mediaDocuments')
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
                ViewAction::make()->modalWidth('xl'),
                EditAction::make()->modalWidth('xl'),
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
            ->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Tipe Dokumen')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama Tipe'),

                    TextEntry::make('code')
                        ->label('Kode')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('max_file_size_mb')
                        ->label('Ukuran Maksimal')
                        ->suffix(' MB'),

                    TextEntry::make('icon')
                        ->label('Icon'),

                    TextEntry::make('weight')
                        ->label('Bobot Nilai Scoring'),

                    TextEntry::make('validity_days')
                        ->label('Masa Berlaku')
                        ->formatStateUsing(fn ($state) => $state ? "{$state} Hari" : 'Selamanya'),

                    IconEntry::make('is_required')
                        ->label('Wajib Diunggah')
                        ->boolean(),

                    IconEntry::make('is_active')
                        ->label('Status Aktif')
                        ->boolean(),

                    TextEntry::make('description')
                        ->label('Deskripsi')
                        ->columnSpanFull(),

                    TextEntry::make('allowed_extensions')
                        ->label('Ekstensi yang Diizinkan')
                        ->badge()
                        ->color('gray')
                        ->separator(',')
                        ->columnSpanFull(),

                    TextEntry::make('created_at')
                        ->label('Dibuat Pada')
                        ->dateTime('d M Y H:i'),
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
            'index' => Pages\ListDocumentTypes::route('/'),
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
