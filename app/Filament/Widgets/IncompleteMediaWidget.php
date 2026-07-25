<?php

namespace App\Filament\Widgets;

use App\Models\Media;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class IncompleteMediaWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Mitra Belum Lengkap';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Media::query()
                    ->where('completeness_percentage', '<', 100)
                    ->orderBy('completeness_percentage', 'asc')
            )
            ->columns([
                TextColumn::make('brand_name')
                    ->label('Nama Media')
                    ->weight('bold')
                    ->description(fn (Media $record) => $record->company_name),

                TextColumn::make('mediaCategory.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label('Pemilik Akun')
                    ->description(fn (Media $record) => $record->user?->email),

                TextColumn::make('completeness_percentage')
                    ->label('Progress Kelengkapan')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->badge()
                    ->color('warning')
                    ->weight('bold'),
            ])
            ->paginated(false);
    }
}
