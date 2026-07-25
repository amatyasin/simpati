<?php

namespace App\Exports;

use App\Models\MediaDocument;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VerificationExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected string $status = 'all',
        protected ?int $documentTypeId = null,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
    ) {}

    public function collection(): Collection
    {
        $query = MediaDocument::with(['mediaPartner', 'documentType', 'verifier'])
            ->orderBy('verified_at', 'desc');

        if ($this->status !== 'all') {
            $query->where('verification_status', $this->status);
        }

        if ($this->documentTypeId) {
            $query->where('document_type_id', $this->documentTypeId);
        }

        if ($this->startDate) {
            $query->whereDate('expiration_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('expiration_date', '<=', $this->endDate);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Media (Brand)',
            'Tipe Dokumen',
            'Nomor Dokumen',
            'Status Verifikasi',
            'Catatan Verifikasi',
            'Verifikator',
            'Tanggal Diverifikasi',
        ];
    }

    public function map($row): array
    {
        static $i = 0;
        $i++;

        $statusLabels = [
            'pending' => 'Menunggu Verifikasi',
            'approved' => 'Disetujui',
            'revision' => 'Butuh Revisi',
            'rejected' => 'Ditolak',
        ];

        return [
            $i,
            $row->mediaPartner?->brand_name ?? '-',
            $row->documentType?->name ?? '-',
            $row->document_number,
            $statusLabels[$row->verification_status?->value] ?? $row->verification_status?->value,
            $row->verification_notes ?? '-',
            $row->verifier?->name ?? '-',
            $row->verified_at ? $row->verified_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F59E0B']], // Amber for verification
            ],
        ];
    }

    public function title(): string
    {
        return 'Data Verifikasi Dokumen';
    }
}
