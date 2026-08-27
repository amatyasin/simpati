<?php

namespace App\Http\Controllers;

use App\Actions\MergeMediaDocumentsAction;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MergedDocumentController extends Controller
{
    /**
     * View/Stream the merged PDF document in the browser inline.
     */
    public function show(Media $media): BinaryFileResponse
    {
        Gate::authorize('view', $media);

        $path = $media->merged_pdf_path;

        if (! $path || ! File::exists($path)) {
            $media = app(MergeMediaDocumentsAction::class)->execute($media);
            $path = $media->merged_pdf_path;
        }

        if (! $path || ! File::exists($path)) {
            abort(404, 'Dokumen PDF gabungan tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="SIMPATI_' . $media->id . '_LEGALITAS.pdf"',
        ]);
    }

    /**
     * Download the merged PDF document.
     */
    public function download(Media $media): BinaryFileResponse
    {
        Gate::authorize('view', $media);

        $path = $media->merged_pdf_path;

        if (! $path || ! File::exists($path)) {
            $media = app(MergeMediaDocumentsAction::class)->execute($media);
            $path = $media->merged_pdf_path;
        }

        if (! $path || ! File::exists($path)) {
            abort(404, 'Dokumen PDF gabungan tidak ditemukan.');
        }

        return response()->download($path, 'SIMPATI_' . $media->id . '_LEGALITAS.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
