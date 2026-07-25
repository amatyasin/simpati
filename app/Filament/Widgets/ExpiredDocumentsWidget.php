<?php

namespace App\Filament\Widgets;

use App\Models\ExpiredDocumentView;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiredDocumentsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Dokumen Kedaluwarsa';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ExpiredDocumentView::query()
                    ->orderBy('expiration_date', 'asc')
            )
            ->columns([
                TextColumn::make('brand_name')
                    ->label('Nama Media')
                    ->weight('semibold'),

                TextColumn::make('document_type_name')
                    ->label('Tipe Dokumen')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('document_number')
                    ->label('Nomor Dokumen'),

                TextColumn::make('expiration_date')
                    ->label('Tanggal Kedaluwarsa')
                    ->date('d M Y')
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated(false);
    }
}
