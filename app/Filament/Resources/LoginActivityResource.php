<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginActivityResource\Pages\ListLoginActivities;
use App\Filament\Resources\LoginActivityResource\Pages\ViewLoginActivity;
use App\Models\LoginActivity;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
// use Filament\Tables\Columns\BadgeColumn; // removed
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextColumn::make('user.name')
                    ->label('User')
                    ->formatStateUsing(function ($record) {
                        if (! $record->user) {
                            return 'Guest / Unknown';
                        }

                        return $record->user->trashed()
                            ? $record->user->name.' (Dihapus)'
                            : $record->user->name;
                    })
                    ->searchable()
                    ->sortable(),
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
            Section::make('Detail Log Login')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Pengguna')
                        ->formatStateUsing(function ($record) {
                            if (! $record->user) {
                                return 'Guest / Unknown';
                            }

                            return $record->user->trashed()
                                ? $record->user->name.' (Dihapus)'
                                : $record->user->name;
                        })
                        ->badge()
                        ->color(fn ($record) => $record->user?->trashed() ? 'danger' : 'primary'),

                    TextEntry::make('status')
                        ->label('Status Login')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'successful' => 'success',
                            'locked' => 'warning',
                            default => 'danger',
                        }),

                    TextEntry::make('ip_address')
                        ->label('IP Address'),

                    TextEntry::make('login_at')
                        ->label('Waktu Login')
                        ->dateTime('d M Y H:i:s'),

                    TextEntry::make('logout_at')
                        ->label('Waktu Logout')
                        ->dateTime('d M Y H:i:s')
                        ->placeholder(fn ($record) => in_array($record->status, ['failed', 'locked']) ? '— (Login Gagal)' : 'Belum Logout / Sesi Aktif'),

                    TextEntry::make('user_agent')
                        ->label('User Agent / Browser')
                        ->columnSpan(3)
                        ->placeholder('—'),
                ])->columns(4),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoginActivities::route('/'),
            'view' => ViewLoginActivity::route('/{record}'),
        ];
    }
}
