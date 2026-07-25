<?php

namespace App\Filament\Resources\MediaDocumentResource\Pages;

use App\Enums\DocumentVerificationStatus;
use App\Filament\Resources\MediaDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaDocument extends CreateRecord
{
    protected static string $resource = MediaDocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['verification_status'] = DocumentVerificationStatus::PENDING->value;

        return $data;
    }
}
