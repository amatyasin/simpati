<?php

namespace App\Services;

use App\Enums\DocumentVerificationStatus;
use App\Models\Media;
use App\Models\MediaDocument;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify the media partner when a document's verification status changes.
     */
    public function notifyDocumentStatusChange(MediaDocument $document): void
    {
        $media = $document->mediaPartner;
        if (! $media) {
            return;
        }

        $user = $media->user;
        if (! $user) {
            return;
        }

        $docTypeName = $document->documentType?->name ?? 'Dokumen';
        $status      = $document->verification_status;

        [$title, $body, $type] = match ($status) {
            DocumentVerificationStatus::APPROVED => [
                'Dokumen Disetujui ✅',
                "Dokumen **{$docTypeName}** (No: {$document->document_number}) telah disetujui oleh verifikator.",
                'success',
            ],
            DocumentVerificationStatus::REJECTED => [
                'Dokumen Ditolak ❌',
                "Dokumen **{$docTypeName}** (No: {$document->document_number}) ditolak. Alasan: ".($document->verification_notes ?? '-'),
                'danger',
            ],
            DocumentVerificationStatus::REVISION => [
                'Revisi Dokumen Diperlukan ⚠️',
                "Dokumen **{$docTypeName}** (No: {$document->document_number}) memerlukan revisi. Catatan: ".($document->verification_notes ?? '-'),
                'warning',
            ],
            default => [null, null, null],
        };

        if ($title === null) {
            return;
        }

        try {
            $notification = Notification::make()
                ->title($title)
                ->body($body);

            match ($type) {
                'success' => $notification->success(),
                'danger'  => $notification->danger(),
                'warning' => $notification->warning(),
                default   => $notification->info(),
            };

            $notification->sendToDatabase($user);
        } catch (\Throwable $e) {
            Log::error('NotificationService: Failed to send database notification', [
                'user_id'     => $user->id,
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins about a newly submitted (pending) document.
     */
    public function notifyAdminsOfNewDocument(MediaDocument $document): void
    {
        $media       = $document->mediaPartner;
        $docTypeName = $document->documentType?->name ?? 'Dokumen';

        $adminUsers = \App\Models\User::role(['super_admin', 'diskominfo_admin'])->get();

        foreach ($adminUsers as $admin) {
            try {
                Notification::make()
                    ->title('Dokumen Baru Menunggu Verifikasi')
                    ->body("**{$media?->brand_name}** mengunggah {$docTypeName} baru yang memerlukan verifikasi.")
                    ->warning()
                    ->sendToDatabase($admin);
            } catch (\Throwable $e) {
                Log::error('NotificationService: Failed to notify admin', [
                    'admin_id'    => $admin->id,
                    'document_id' => $document->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify the media partner when a document has expired.
     */
    public function notifyDocumentExpired(MediaDocument $document): void
    {
        $media = $document->mediaPartner;
        if (! $media) {
            return;
        }

        $user = $media->user;
        if (! $user) {
            return;
        }

        $docTypeName = $document->documentType?->name ?? 'Dokumen';

        try {
            Notification::make()
                ->title('Dokumen Kedaluwarsa 🚨')
                ->body("Dokumen **{$docTypeName}** (No: {$document->document_number}) masa berlakunya telah kedaluwarsa pada " . \Carbon\Carbon::parse($document->expiration_date)->format('d M Y') . ".")
                ->danger()
                ->sendToDatabase($user);
        } catch (\Throwable $e) {
            Log::error('NotificationService: Failed to send document expired notification', [
                'user_id'     => $user->id,
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify the media partner when their profile completeness is below 100%.
     */
    public function notifyProfileIncomplete(Media $media): void
    {
        $user = $media->user;
        if (! $user) {
            return;
        }

        try {
            Notification::make()
                ->title('Kelengkapan Profil Kurang ⚠️')
                ->body("Profil media **{$media->brand_name}** belum lengkap ({$media->completeness_percentage}%). Silakan unggah seluruh dokumen wajib.")
                ->warning()
                ->sendToDatabase($user);
        } catch (\Throwable $e) {
            Log::error('NotificationService: Failed to send profile incomplete notification', [
                'user_id'  => $media->user_id,
                'media_id' => $media->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
