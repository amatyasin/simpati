<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Enums\MediaVerificationStatus;
use App\Filament\Resources\MediaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // Media partner only sees all (which is scoped to their profile anyway)
        if (auth()->user()?->hasRole('media_partner')) {
            return [];
        }

        return [
            'all' => Tab::make('Semua Mitra'),
            'pending' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', MediaVerificationStatus::PENDING->value)),
            'approved' => Tab::make('Terverifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', MediaVerificationStatus::APPROVED->value)),
            'revision' => Tab::make('Butuh Revisi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', MediaVerificationStatus::REVISION->value)),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', MediaVerificationStatus::REJECTED->value)),
        ];
    }
}
