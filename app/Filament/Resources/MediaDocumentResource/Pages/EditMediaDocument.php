<?php

namespace App\Filament\Resources\MediaDocumentResource\Pages;

use App\Enums\DocumentVerificationStatus;
use App\Filament\Resources\MediaDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMediaDocument extends EditRecord
{
    protected static string $resource = MediaDocumentResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // When media partner re-uploads, reset to pending
        if (auth()->user()?->hasRole('media_partner')) {
            $data['verification_status'] = DocumentVerificationStatus::PENDING->value;
            $data['verification_notes']  = null;
            $data['verifier_id']         = null;
            $data['verified_at']         = null;
        }

        return $data;
    }
}
