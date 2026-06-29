<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Setting;
use App\Services\LogoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentPdfController extends Controller
{
    public function __invoke(Document $document, Request $request, LogoService $logos)
    {
        $document->load(['client', 'items.product', 'payments']);
        $settings = Setting::pluck('value', 'key');
        $settings['pdf_logo_path'] = $logos->pdfPath($settings->get('logo_path'));
        $pdf = Pdf::loadView('pdf.document', compact('document', 'settings'))->setPaper('a4');
        $filename = $document->number.'.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}