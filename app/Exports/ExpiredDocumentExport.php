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

class ExpiredDocumentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return DB::table('view_expired_documents')
            ->orderBy('expiration_date', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Media (Brand)',
            'Tipe Dokumen',
            'Nomor Dokumen',
            'Tanggal Kedaluwarsa',
        ];
    }

    public function map($row): array
    {
        static $i = 0;
        $i++;

        return [
            $i,
            $row->brand_name,
            $row->document_type_name,
            $row->document_number,
            $row->expiration_date ? date('d/m/Y', strtotime($row->expiration_date)) : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EF4444']], // Red for expired
            ],
        ];
    }

    public function title(): string
    {
        return 'Dokumen Kedaluwarsa';
    }
}
