<?php

namespace App\Filament\Resources\MediaServiceTypeResource\Pages;

use App\Filament\Resources\MediaServiceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaServiceTypes extends ListRecords
{
    protected static string $resource = MediaServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Tambah Jenis Layanan')
                ->modalWidth('xl'),
        ];
    }
}
