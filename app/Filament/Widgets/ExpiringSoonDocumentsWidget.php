<?php

namespace App\Filament\Widgets;

use App\Models\MediaDocument;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiringSoonDocumentsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'Dokumen Segera Kedaluwarsa';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = MediaDocument::query()
            ->expiringSoon(30)
            ->with(['mediaPartner', 'documentType'])
            ->orderBy('expiration_date', 'asc');

        if (auth()->user()?->hasRole('media_partner')) {
            $query->whereHas('mediaPartner', fn ($q) => $q->where('user_id', auth()->id()));
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('mediaPartner.brand_name')
                    ->label('Mitra Media')
                    ->weight('bold'),

                TextColumn::make('documentType.name')
                    ->label('Tipe Dokumen')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('document_number')
                    ->label('Nomor Dokumen'),

                TextColumn::make('expiration_date')
                    ->label('Tanggal Berakhir')
                    ->date('d M Y')
                    ->color('warning')
                    ->weight('bold'),
            ])
            ->paginated(false);
    }
}
