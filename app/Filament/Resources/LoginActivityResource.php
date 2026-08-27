<?php

namespace App\Filament\Resources;

use App\Models\LoginActivity;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Columns\BadgeColumn; // removed
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;

class LoginActivityResource extends Resource
{
    protected static ?string $model = LoginActivity::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';
    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    public static function form(Schema $schema): Schema
    {
        // No editable form needed for read‑only resource
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->default('Guest / Unknown')->searchable()->sortable(),
                TextColumn::make('login_at')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('logout_at')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('ip_address')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'locked' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\LoginActivityResource\Pages\ListLoginActivities::route('/'),
            'view' => \App\Filament\Resources\LoginActivityResource\Pages\ViewLoginActivity::route('/{record}'),
        ];
    }
}
