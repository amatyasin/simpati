<?php

namespace App\Exports;

use App\Models\Media;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MediaExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected string $status = 'all',
        protected ?int $categoryId = null,
        protected ?int $minScore = null,
        protected ?int $maxScore = null,
        protected ?int $minCompleteness = null,
        protected ?int $maxCompleteness = null,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
    ) {}

    public function collection(): Collection
    {
        $query = Media::with(['mediaCategory', 'user'])
            ->orderBy('brand_name');

        if ($this->status !== 'all') {
            $query->where('verification_status', $this->status);
        }

        if ($this->categoryId) {
            $query->where('media_category_id', $this->categoryId);
        }

        if ($this->minScore !== null) {
            $query->where('verification_score', '>=', $this->minScore);
        }

        if ($this->maxScore !== null) {
            $query->where('verification_score', '<=', $this->maxScore);
        }

        if ($this->minCompleteness !== null) {
            $query->where('completeness_percentage', '>=', $this->minCompleteness);
        }

        if ($this->maxCompleteness !== null) {
            $query->where('completeness_percentage', '<=', $this->maxCompleteness);
        }

        if ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate . ' 00:00:00');
        }

        if ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate . ' 23:59:59');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Media (Brand)',
            'Nama Perusahaan',
            'Kategori',
            'Website',
            'Email',
            'Telepon',
            'Status Verifikasi',
            'Skor Verifikasi (%)',
            'Kelengkapan Dokumen (%)',
            'Terdaftar Sejak',
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
            $row->mediaCategory?->name ?? '-',
            $row->website ?? '-',
            $row->email ?? '-',
            $row->phone ?? '-',
            $statusLabels[$row->verification_status] ?? $row->verification_status,
            $row->verification_score.'%',
            $row->completeness_percentage.'%',
            $row->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Daftar Mitra Media';
    }
}
