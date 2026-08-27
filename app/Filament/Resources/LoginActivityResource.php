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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Log Login')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('user.name')
                        ->label('Pengguna')
                        ->default('Guest / Unknown')
                        ->badge()
                        ->color('primary'),

                    \Filament\Infolists\Components\TextEntry::make('status')
                        ->label('Status Login')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'successful' => 'success',
                            'locked'     => 'warning',
                            default      => 'danger',
                        }),

                    \Filament\Infolists\Components\TextEntry::make('ip_address')
                        ->label('IP Address'),

                    \Filament\Infolists\Components\TextEntry::make('login_at')
                        ->label('Waktu Login')
                        ->dateTime('d M Y H:i:s'),

                    \Filament\Infolists\Components\TextEntry::make('logout_at')
                        ->label('Waktu Logout')
                        ->dateTime('d M Y H:i:s')
                        ->placeholder('Belum Logout / Sesi Aktif'),

                    \Filament\Infolists\Components\TextEntry::make('user_agent')
                        ->label('User Agent / Browser')
                        ->columnSpan(3)
                        ->placeholder('—'),
                ])->columns(4),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\LoginActivityResource\Pages\ListLoginActivities::route('/'),
            'view' => \App\Filament\Resources\LoginActivityResource\Pages\ViewLoginActivity::route('/{record}'),
        ];
    }
}
