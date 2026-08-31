<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Actions\MergeMediaDocumentsAction;
use App\Filament\Resources\MediaResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMedia extends ViewRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('merge_pdf')
                ->label(fn ($record): string => $record->merged_pdf_url ? 'Generate Ulang PDF' : 'Gabungkan Dokumen PDF')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->action(function ($record): void {
                    if ($record->mediaDocuments()->count() === 0) {
                        Notification::make()
                            ->title('Belum ada dokumen yang diunggah.')
                            ->body('Silakan unggah setidaknya 1 dokumen terlebih dahulu.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        app(MergeMediaDocumentsAction::class)->execute($record);
                        Notification::make()
                            ->title('PDF gabungan berhasil dibuat.')
                            ->body("Dokumen tersedia: {$record->available_documents_count} dari {$record->total_required_documents_count} dokumen wajib.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal menggabungkan PDF')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('view_merged_pdf')
                ->label('Lihat PDF')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->visible(fn ($record): bool => $record->merged_pdf_url !== null)
                ->url(fn ($record): string => route('media.merged-pdf.show', $record))
                ->openUrlInNewTab(),

            Actions\Action::make('download_merged_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn ($record): bool => $record->merged_pdf_url !== null)
                ->url(fn ($record): string => route('media.merged-pdf.download', $record)),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
