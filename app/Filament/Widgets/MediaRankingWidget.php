<?php

namespace App\Filament\Widgets;

use App\Models\MediaRanking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MediaRankingWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Peringkat Kelayakan Media';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! auth()->user()?->hasRole('media_partner');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MediaRanking::query()
                    ->orderBy('rank', 'asc')
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('Peringkat')
                    ->weight('bold')
                    ->alignment('center'),

                TextColumn::make('brand_name')
                    ->label('Nama Media (Brand)')
                    ->weight('semibold')
                    ->description(fn ($record) => $record->company_name),

                TextColumn::make('category_name')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('verification_score')
                    ->label('Skor Verifikasi')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->badge()
                    ->color('success'),

                TextColumn::make('completeness_percentage')
                    ->label('Kelengkapan')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->badge()
                    ->color('info'),

                TextColumn::make('ranking_score')
                    ->label('Skor Peringkat')
                    ->numeric(1)
                    ->weight('bold'),
            ])
            ->paginated(false);
    }
}
