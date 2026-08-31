<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionViewerResource\Pages\ListPermissions;
use App\Filament\Resources\PermissionViewerResource\Pages\ViewPermission;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionViewerResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

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
                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->getStateUsing(fn (Permission $record) => $record->roles->count()),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'view' => ViewPermission::route('/{record}'),
        ];
    }
}
