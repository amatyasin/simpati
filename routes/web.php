<?php

use App\Http\Controllers\MergedDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/media/{media}/merged-pdf/view', [MergedDocumentController::class, 'show'])
        ->name('media.merged-pdf.show');

    Route::get('/media/{media}/merged-pdf/download', [MergedDocumentController::class, 'download'])
        ->name('media.merged-pdf.download');
});
