<?php

namespace App\Filament\Widgets;

use App\Enums\MediaVerificationStatus;
use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user && $user->hasRole('media_partner')) {
            $mediaId = Media::where('user_id', $user->id)->value('id');
            $media = Media::where('user_id', $user->id)->first();
            
            $totalDocs = \App\Models\MediaDocument::where('media_id', $mediaId)->count();
            $pendingDocs = \App\Models\MediaDocument::where('media_id', $mediaId)->pending()->count();
            $expiredDocs = \App\Models\MediaDocument::where('media_id', $mediaId)->expired()->count();
            $completeness = $media?->completeness_percentage ?? 0;

            return [
                Stat::make('Kelengkapan Profil', $completeness . '%')
                    ->description('Persentase kelengkapan data')
                    ->descriptionIcon('heroicon-o-check-badge')
                    ->color($completeness >= 80 ? 'success' : 'warning')
                    ->chart([$completeness, $completeness]),

                Stat::make('Total Dokumen', $totalDocs)
                    ->description('Dokumen yang diunggah')
                    ->descriptionIcon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->chart([0, 1, 2, $totalDocs]),

                Stat::make('Dokumen Menunggu', $pendingDocs)
                    ->description('Menunggu verifikasi admin')
                    ->descriptionIcon('heroicon-o-clock')
                    ->color($pendingDocs > 0 ? 'warning' : 'success')
                    ->chart([0, 1, 2, $pendingDocs]),

                Stat::make('Dokumen Kedaluwarsa', $expiredDocs)
                    ->description('Dokumen yang tidak berlaku')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color($expiredDocs > 0 ? 'danger' : 'success')
                    ->chart([0, 1, 2, $expiredDocs]),
            ];
        }

        $totalMitra = Media::count();
        $approvedMitra = Media::where('verification_status', MediaVerificationStatus::APPROVED->value)->count();

        // Query optimized database views
        $verificationStats = DB::table('view_verification_statistics')->first();
        $pendingDocs = $verificationStats->total_pending ?? 0;

        $expiredDocs = DB::table('view_expired_documents')->count();
        $expiringSoon = DB::table('view_expiring_soon_documents')->count();

        return [
            Stat::make('Total Mitra Media', $totalMitra)
                ->description("{$approvedMitra} mitra terverifikasi")
                ->descriptionIcon('heroicon-o-newspaper')
                ->color('primary')
                ->chart([1, 3, 5, 7, 9, $totalMitra]),

            Stat::make('Dokumen Menunggu', $pendingDocs)
                ->description('Perlu diverifikasi segera')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingDocs > 0 ? 'warning' : 'success')
                ->chart([0, 1, 2, 3, 4, $pendingDocs]),

            Stat::make('Dokumen Kedaluwarsa', $expiredDocs)
                ->description("{$expiringSoon} akan kedaluwarsa ≤ 30 hari")
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($expiredDocs > 0 ? 'danger' : 'success')
                ->chart([0, 0, 1, 2, 3, $expiredDocs]),

            Stat::make('Kategori Media', MediaCategory::where('is_active', true)->count())
                ->description('Kategori aktif')
                ->descriptionIcon('heroicon-o-tag')
                ->color('info')
                ->chart([3, 4, 5, 6, 7, 8]),

            Stat::make('Tipe Dokumen', DocumentType::where('is_active', true)->count())
                ->description('Tipe dokumen aktif')
                ->descriptionIcon('heroicon-o-document-duplicate')
                ->color('warning')
                ->chart([2, 3, 4, 5, 6, 6]),

        ];
    }
}
