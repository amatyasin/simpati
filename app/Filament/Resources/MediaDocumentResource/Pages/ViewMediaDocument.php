<?php

namespace App\Filament\Resources\MediaDocumentResource\Pages;

use App\Actions\RejectDocumentAction;
use App\Actions\RequestRevisionAction;
use App\Actions\VerifyDocumentAction;
use App\Filament\Resources\MediaDocumentResource;
use App\Models\MediaDocument;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMediaDocument extends ViewRecord
{
    protected static string $resource = MediaDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('verify')
                ->label('Verifikasi')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin']))
                ->form([
                    Select::make('decision')
                        ->label('Keputusan Verifikasi')
                        ->options([
                            'approved' => '✅ Setujui Dokumen',
                            'revision' => '✏️ Minta Revisi',
                            'rejected' => '❌ Tolak Dokumen',
                        ])
                        ->required()
                        ->live(),

                    Textarea::make('notes')
                        ->label('Catatan Verifikasi')
                        ->rows(3)
                        ->required(fn ($get) => in_array($get('decision'), ['revision', 'rejected'])),
                ])
                ->action(function (array $data): void {
                    /** @var MediaDocument $record */
                    $record = $this->getRecord();
                    $verifierId = auth()->id();

                    match ($data['decision']) {
                        'approved' => app(VerifyDocumentAction::class)->execute($record, $verifierId, $data['notes'] ?? null),
                        'rejected' => app(RejectDocumentAction::class)->execute($record, $verifierId, $data['notes']),
                        'revision' => app(RequestRevisionAction::class)->execute($record, $verifierId, $data['notes']),
                    };

                    Notification::make()
                        ->title('Keputusan verifikasi berhasil disimpan.')
                        ->success()
                        ->send();

                    $this->fillForm();
                }),

            Action::make('download')
                ->label('Unduh Dokumen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => $this->getRecord()->getFirstMediaUrl('documents'))
                ->openUrlInNewTab()
                ->visible(fn () => $this->getRecord()->getFirstMediaUrl('documents') !== ''),

            Actions\DeleteAction::make(),
        ];
    }
}
