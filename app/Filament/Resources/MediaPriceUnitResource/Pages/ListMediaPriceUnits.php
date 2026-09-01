<?php

namespace App\Filament\Resources\MediaPriceUnitResource\Pages;

use App\Filament\Resources\MediaPriceUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaPriceUnits extends ListRecords
{
    protected static string $resource = MediaPriceUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Tambah Satuan Harga')
                ->modalWidth('lg'),
        ];
    }
}
