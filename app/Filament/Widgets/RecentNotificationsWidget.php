<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Notifications\DatabaseNotification;

class RecentNotificationsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Notifikasi Terbaru';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', auth()->user()?->getMorphClass())
                    ->where('notifiable_id', auth()->id())
                    ->whereNull('read_at')
                    ->latest()
            )
            ->columns([
                TextColumn::make('data.title')
                    ->label('Judul')
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('data.body')
                    ->label('Pesan')
                    ->wrap()
                    ->html(),

                TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->since()
                    ->color('gray'),
            ])
            ->actions([
                Action::make('markAsRead')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->button()
                    ->action(function (DatabaseNotification $record) {
                        $record->markAsRead();
                    }),
            ])
            ->headerActions([
                Action::make('markAllAsRead')
                    ->label('Tandai Semua Dibaca')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->button()
                    ->action(function () {
                        if (auth()->check()) {
                            auth()->user()->unreadNotifications->markAsRead();
                        }
                    })
                    ->visible(fn () => auth()->check() && auth()->user()->unreadNotifications()->exists()),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
