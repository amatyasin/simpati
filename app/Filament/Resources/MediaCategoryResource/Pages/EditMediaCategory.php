<?php

namespace App\Filament\Resources\MediaCategoryResource\Pages;

use App\Filament\Resources\MediaCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMediaCategory extends EditRecord
{
    protected static string $resource = MediaCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
