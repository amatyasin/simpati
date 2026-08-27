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
        $query = \App\Models\MediaDocument::query()
            ->expired()
            ->with(['mediaPartner', 'documentType'])
            ->orderBy('expiration_date', 'asc');

        if (auth()->user()?->hasRole('media_partner')) {
            $query->whereHas('mediaPartner', fn ($q) => $q->where('user_id', auth()->id()));
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('mediaPartner.brand_name')
                    ->label('Nama Media')
                    ->weight('semibold'),

                TextColumn::make('documentType.name')
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
