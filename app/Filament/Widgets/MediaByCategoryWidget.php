<?php

namespace App\Filament\Widgets;

use App\Models\MediaCategory;
use Filament\Widgets\ChartWidget;

class MediaByCategoryWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Sebaran Mitra per Kategori';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = MediaCategory::withCount('media')
            ->where('is_active', true)
            ->orderByDesc('media_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Mitra',
                    'data' => $data->pluck('media_count')->toArray(),
                    'backgroundColor' => [
                        '#6366F1', '#3B82F6', '#10B981', '#F59E0B',
                        '#EF4444', '#8B5CF6', '#14B8A6', '#F97316',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'right'],
            ],
        ];
    }
}
