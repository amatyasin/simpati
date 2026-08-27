<?php

namespace App\Actions;

use App\Models\Media;
use App\Models\MediaDocument;
use App\Models\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class MergeMediaDocumentsAction
{
    /**
     * Merge all available documents for a Media profile into a single PDF file.
     *
     * @param Media $media
     * @return Media
     * @throws \RuntimeException
     */
    public function execute(Media $media): Media
    {
        // 1. Fetch available documents sorted by DocumentType weight / id
        $documents = $media->mediaDocuments()
            ->with(['documentType'])
            ->get()
            ->sortBy(function (MediaDocument $doc) {
                return $doc->documentType?->weight ?? $doc->documentType?->id ?? 999;
            })
            ->values();

        if ($documents->isEmpty()) {
            throw new \RuntimeException('Tidak ada dokumen yang tersedia untuk digabungkan.');
        }

        $totalRequiredCount = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->count();
        $availableCount = $documents->count();

        $tempFilesToDelete = [];

        try {
            $fpdi = new Fpdi();

            // 2. Generate Cover Page
            $coverHtml = view('pdf.media-merged-pdf', [
                'media' => $media,
                'documents' => $documents,
                'availableCount' => $availableCount,
                'totalRequiredCount' => $totalRequiredCount,
            ])->render();

            $coverPdfContent = Pdf::loadHTML($coverHtml)->setPaper('a4', 'portrait')->output();
            $coverTempPath = sys_get_temp_dir() . '/cover_' . uniqid() . '.pdf';
            file_put_contents($coverTempPath, $coverPdfContent);
            $tempFilesToDelete[] = $coverTempPath;

            $this->appendPdfToFpdi($fpdi, $coverTempPath);

            // 3. Append Each Document (with Section Header)
            foreach ($documents as $index => $doc) {
                $docTypeName = $doc->documentType?->name ?? 'Dokumen';
                $statusStr = is_object($doc->verification_status)
                    ? $doc->verification_status->value
                    : (string) $doc->verification_status;

                // Render Section Header Separator
                $headerHtml = view('pdf.media-document-header', [
                    'number' => $index + 1,
                    'documentTypeName' => $docTypeName,
                    'documentNumber' => $doc->document_number,
                    'status' => $statusStr,
                ])->render();

                $headerPdfContent = Pdf::loadHTML($headerHtml)->setPaper('a4', 'portrait')->output();
                $headerTempPath = sys_get_temp_dir() . '/header_' . uniqid() . '.pdf';
                file_put_contents($headerTempPath, $headerPdfContent);
                $tempFilesToDelete[] = $headerTempPath;

                $this->appendPdfToFpdi($fpdi, $headerTempPath);

                // Process Attached File
                $mediaItem = $doc->getFirstMedia('documents');
                if (! $mediaItem || ! File::exists($mediaItem->getPath())) {
                    continue;
                }

                $filePath = $mediaItem->getPath();
                $mimeType = $mediaItem->mime_type ?? mime_content_type($filePath);

                if (str_contains($mimeType, 'pdf')) {
                    $this->appendPdfToFpdi($fpdi, $filePath);
                } elseif (str_contains($mimeType, 'image')) {
                    $imgData = base64_encode(File::get($filePath));
                    $imgSrc = 'data:' . $mimeType . ';base64,' . $imgData;
                    $imgHtml = '<html><head><style>@page { margin: 0; } body { margin: 0; text-align: center; background-color: #ffffff; }</style></head><body><img src="' . $imgSrc . '" style="max-width:100%; max-height:100vh; object-fit:contain;" /></body></html>';

                    $imgPdfContent = Pdf::loadHTML($imgHtml)->setPaper('a4', 'portrait')->output();
                    $imgTempPath = sys_get_temp_dir() . '/img_' . uniqid() . '.pdf';
                    file_put_contents($imgTempPath, $imgPdfContent);
                    $tempFilesToDelete[] = $imgTempPath;

                    $this->appendPdfToFpdi($fpdi, $imgTempPath);
                }
            }

            // 4. Output Merged PDF to Temporary File
            $mergedTempPath = sys_get_temp_dir() . '/SIMPATI_' . $media->id . '_LEGALITAS_' . uniqid() . '.pdf';
            $fpdi->Output('F', $mergedTempPath);
            $tempFilesToDelete[] = $mergedTempPath;

            // 5. Attach Result to Spatie Media Library Collection
            $fileName = 'SIMPATI_' . $media->id . '_LEGALITAS.pdf';
            $media->addMedia($mergedTempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('merged_documents', 'public');

        } finally {
            // Clean up temporary files
            foreach ($tempFilesToDelete as $file) {
                if (File::exists($file)) {
                    @unlink($file);
                }
            }
        }

        return $media->fresh();
    }

    /**
     * Import all pages of a PDF file into FPDI instance.
     */
    protected function appendPdfToFpdi(Fpdi $fpdi, string $pdfFilePath): void
    {
        $pageCount = $fpdi->setSourceFile($pdfFilePath);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $fpdi->importPage($pageNo);
            $size = $fpdi->getTemplateSize($templateId);
            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);
        }
    }
}
