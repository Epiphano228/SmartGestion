<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function invoices(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Numéro', 'Client', 'Date', 'Échéance', 'Statut', 'HT', 'TVA', 'Total', 'Payé', 'Solde'], ';');
            Document::with('client')->invoices()->latest('issued_at')->chunk(200, function ($documents) use ($out) {
                foreach ($documents as $d) {
                    fputcsv($out, [$d->number, $d->client->company_name, $d->issued_at->format('d/m/Y'), $d->due_at?->format('d/m/Y'), $d->status->label(), $d->subtotal, $d->tax_total, $d->total, $d->paid_total, $d->balance], ';');
                }
            });
            fclose($out);
        }, 'factures-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
