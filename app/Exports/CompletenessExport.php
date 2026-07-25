<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompletenessExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return DB::table('view_media_completeness')
            ->orderBy('completeness_percentage', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Media (Brand)',
            'Nama Perusahaan',
            'Kategori',
            'Kelengkapan Dokumen (%)',
            'Status Verifikasi',
        ];
    }

    public function map($row): array
    {
        static $i = 0;
        $i++;

        $statusLabels = [
            'draft' => 'Draft',
            'pending' => 'Menunggu Verifikasi',
            'approved' => 'Terverifikasi',
            'revision' => 'Butuh Revisi',
            'rejected' => 'Ditolak',
        ];

        return [
            $i,
            $row->brand_name,
            $row->company_name,
            $row->category_name ?? '-',
            $row->completeness_percentage.'%',
            $statusLabels[$row->verification_status] ?? $row->verification_status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']], // Green for completeness
            ],
        ];
    }

    public function title(): string
    {
        return 'Kelengkapan Dokumen Media';
    }
}
