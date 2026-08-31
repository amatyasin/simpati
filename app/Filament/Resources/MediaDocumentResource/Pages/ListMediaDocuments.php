<?php

namespace App\Filament\Resources\MediaDocumentResource\Pages;

use App\Enums\DocumentVerificationStatus;
use App\Filament\Resources\MediaDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMediaDocuments extends ListRecords
{
    protected static string $resource = MediaDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('xl'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Dokumen'),
            'pending' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', DocumentVerificationStatus::PENDING->value)),
            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', DocumentVerificationStatus::APPROVED->value)),
            'revision' => Tab::make('Perlu Revisi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', DocumentVerificationStatus::REVISION->value)),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', DocumentVerificationStatus::REJECTED->value)),
            'expired' => Tab::make('Kedaluwarsa')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('expiration_date')->where('expiration_date', '<', now()->startOfDay())),
        ];
    }
}
