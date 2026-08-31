<?php

namespace App\Filament\Resources\MediaPriceResource\Pages;

use App\Enums\MediaPriceStatus;
use App\Filament\Resources\MediaPriceResource;
use App\Models\Media;
use App\Models\MediaPrice;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMediaPrices extends ListRecords
{
    protected static string $resource = MediaPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Tambah Harga Baru')
                ->modalWidth('xl')
                ->mutateFormDataUsing(function (array $data): array {
                    if (auth()->user()?->hasRole('media_partner')) {
                        $data['status'] = MediaPriceStatus::DRAFT->value;

                        if (empty($data['media_id'])) {
                            $userMedia = Media::where('user_id', auth()->id())->first();
                            if ($userMedia) {
                                $data['media_id'] = $userMedia->id;
                            }
                        }
                    }

                    return $data;
                })
                ->before(function (CreateAction $action, array $data): void {
                    $mediaId = $data['media_id'] ?? null;

                    if (! $mediaId && auth()->user()?->hasRole('media_partner')) {
                        $userMedia = Media::where('user_id', auth()->id())->first();
                        $mediaId = $userMedia?->id;
                    }

                    if (! $mediaId) {
                        Notification::make()
                            ->title('Gagal Menyimpan Harga')
                            ->body('Media partner belum terdaftar.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }

                    $serviceType = $data['service_type'];
                    $status = $data['status'] ?? 'draft';

                    if ($status === MediaPriceStatus::ACTIVE->value) {
                        $from = $data['effective_from'];
                        $until = $data['effective_until'] ?? null;

                        $overlap = MediaPrice::where('media_id', $mediaId)
                            ->where('service_type', $serviceType)
                            ->where('status', MediaPriceStatus::ACTIVE->value)
                            ->where(function ($q) use ($from, $until) {
                                $q->where('effective_from', '<=', $until ?? '9999-12-31')
                                    ->where(function ($q2) use ($from) {
                                        $q2->whereNull('effective_until')
                                            ->orWhere('effective_until', '>=', $from);
                                    });
                            })
                            ->exists();

                        if ($overlap) {
                            Notification::make()
                                ->title('Gagal Menyimpan Harga')
                                ->body("Sudah ada harga aktif untuk media dan jenis layanan '{$serviceType}' pada periode yang sama.")
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }
                }),
        ];
    }
}
