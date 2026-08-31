<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleViewerResource\Pages\ListRoles;
use App\Filament\Resources\RoleViewerResource\Pages\ViewRole;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleViewerResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        // No forms needed for read‑only resource
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('guard_name')->searchable()->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->getStateUsing(fn (Role $record) => $record->permissions->count()),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detail Role')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama Role')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('guard_name')
                        ->label('Guard Name')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('permissions.name')
                        ->label('Daftar Permission')
                        ->badge()
                        ->color('info')
                        ->separator(', ')
                        ->columnSpanFull()
                        ->placeholder('Tidak ada permission yang diberikan'),

                    TextEntry::make('created_at')
                        ->label('Dibuat Pada')
                        ->dateTime('d M Y H:i'),
                ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'view' => ViewRole::route('/{record}'),
        ];
    }
}
