<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MonitoringPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.monitoring-page';

    protected static ?string $navigationLabel = 'Monitoring';

    protected static ?string $title = 'Pemantauan Kelayakan & Kelengkapan';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership']);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\MediaRankingWidget::class,
            \App\Filament\Widgets\IncompleteMediaWidget::class,
            \App\Filament\Widgets\ExpiredDocumentsWidget::class,
            \App\Filament\Widgets\ExpiringSoonDocumentsWidget::class,
        ];
    }
}
