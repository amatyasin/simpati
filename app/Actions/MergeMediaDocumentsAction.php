<?php

namespace App\Actions;

use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;

class MergeMediaDocumentsAction
{
    /**
     * Merge all available documents for a Media profile into a single PDF file.
     *
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
            $fpdi = new Fpdi;

            // 2. Generate Cover Page
            $coverHtml = view('pdf.media-merged-pdf', [
                'media' => $media,
                'documents' => $documents,
                'availableCount' => $availableCount,
                'totalRequiredCount' => $totalRequiredCount,
            ])->render();

            $coverPdfContent = Pdf::loadHTML($coverHtml)->setPaper('a4', 'portrait')->output();
            $coverTempPath = sys_get_temp_dir().'/cover_'.uniqid().'.pdf';
            file_put_contents($coverTempPath, $coverPdfContent);
            $tempFilesToDelete[] = $coverTempPath;

            $this->appendPdfToFpdi($fpdi, $coverTempPath, $tempFilesToDelete);

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
                $headerTempPath = sys_get_temp_dir().'/header_'.uniqid().'.pdf';
                file_put_contents($headerTempPath, $headerPdfContent);
                $tempFilesToDelete[] = $headerTempPath;

                $this->appendPdfToFpdi($fpdi, $headerTempPath, $tempFilesToDelete);

                // Process Attached File
                $mediaItem = $doc->getFirstMedia('documents');
                if (! $mediaItem || ! File::exists($mediaItem->getPath())) {
                    continue;
                }

                $filePath = $mediaItem->getPath();
                $mimeType = $mediaItem->mime_type ?? mime_content_type($filePath);

                if (str_contains($mimeType, 'pdf')) {
                    $this->appendPdfToFpdi($fpdi, $filePath, $tempFilesToDelete);
                } elseif (str_contains($mimeType, 'image')) {
                    $imgData = base64_encode(File::get($filePath));
                    $imgSrc = 'data:'.$mimeType.';base64,'.$imgData;
                    $imgHtml = '<html><head><style>@page { margin: 0; } body { margin: 0; text-align: center; background-color: #ffffff; }</style></head><body><img src="'.$imgSrc.'" style="max-width:100%; max-height:100vh; object-fit:contain;" /></body></html>';

                    $imgPdfContent = Pdf::loadHTML($imgHtml)->setPaper('a4', 'portrait')->output();
                    $imgTempPath = sys_get_temp_dir().'/img_'.uniqid().'.pdf';
                    file_put_contents($imgTempPath, $imgPdfContent);
                    $tempFilesToDelete[] = $imgTempPath;

                    $this->appendPdfToFpdi($fpdi, $imgTempPath, $tempFilesToDelete);
                }
            }

            // 4. Output Merged PDF to Temporary File
            $mergedTempPath = sys_get_temp_dir().'/SIMPATI_'.$media->id.'_LEGALITAS_'.uniqid().'.pdf';
            $fpdi->Output('F', $mergedTempPath);
            $tempFilesToDelete[] = $mergedTempPath;

            // 5. Attach Result to Spatie Media Library Collection
            $fileName = 'SIMPATI_'.$media->id.'_LEGALITAS.pdf';
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
    protected function appendPdfToFpdi(Fpdi $fpdi, string $pdfFilePath, array &$tempFilesToDelete): void
    {
        $processedPath = $this->normalizePdfFile($pdfFilePath, $tempFilesToDelete);

        try {
            $pageCount = $fpdi->setSourceFile($processedPath);
            if ($pageCount < 1) {
                throw new \RuntimeException('Dokumen PDF tidak memiliki halaman yang dapat dibaca.');
            }
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Gagal membaca PDF ('.basename($pdfFilePath).'): '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Preprocess PDF file using qpdf or ghostscript to disable compressed object streams (PDF 1.5+ compatibility for FPDI).
     */
    protected function normalizePdfFile(string $pdfFilePath, array &$tempFilesToDelete): string
    {
        $outputPath = sys_get_temp_dir().'/norm_'.uniqid().'.pdf';

        $qpdfPath = $this->getBinaryPath('qpdf');
        if ($qpdfPath) {
            $cmd = sprintf(
                '%s --object-streams=disable %s %s 2>&1',
                escapeshellarg($qpdfPath),
                escapeshellarg($pdfFilePath),
                escapeshellarg($outputPath)
            );
            @exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath) && File::size($outputPath) > 0) {
                $tempFilesToDelete[] = $outputPath;

                return $outputPath;
            }
        }

        $gsPath = $this->getBinaryPath('gs');
        if ($gsPath) {
            $cmd = sprintf(
                '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                escapeshellarg($gsPath),
                escapeshellarg($outputPath),
                escapeshellarg($pdfFilePath)
            );
            @exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath) && File::size($outputPath) > 0) {
                $tempFilesToDelete[] = $outputPath;

                return $outputPath;
            }
        }

        return $pdfFilePath;
    }

    /**
     * Find binary executable path.
     */
    protected function getBinaryPath(string $binaryName): ?string
    {
        $candidates = [
            '/opt/homebrew/bin/'.$binaryName,
            '/usr/local/bin/'.$binaryName,
            '/usr/bin/'.$binaryName,
            '/bin/'.$binaryName,
        ];

        foreach ($candidates as $path) {
            if (File::exists($path) && is_executable($path)) {
                return $path;
            }
        }

        $which = trim((string) @shell_exec("which {$binaryName} 2>/dev/null"));
        if ($which && File::exists($which) && is_executable($which)) {
            return $which;
        }

        return null;
    }
}
