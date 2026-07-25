<?php

namespace App\Filament\Resources;

use Spatie\Permission\Models\Role;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use App\Filament\Resources\RoleViewerResource\Pages;


class RoleViewerResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
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
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->getStateUsing(fn (Role $record) => $record->permissions->count()),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RoleViewerResource\Pages\ListRoles::route('/'),
            'view' => \App\Filament\Resources\RoleViewerResource\Pages\ViewRole::route('/{record}'),
        ];
    }
}
