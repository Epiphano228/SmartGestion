<?php

namespace App\View\Composers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppLayoutComposer
{
    public function compose(View $view): void
    {
        $counts = ['clients' => 0, 'products' => 0, 'quotations' => 0, 'proformas' => 0, 'invoices' => 0, 'overdue' => 0];

        if (auth()->check() && Schema::hasTable('documents')) {
            Document::invoices()
                ->whereIn('status', [DocumentStatus::Sent->value, DocumentStatus::Partial->value])
                ->whereDate('due_at', '<', today())
                ->whereColumn('paid_total', '<', 'total')
                ->update(['status' => DocumentStatus::Overdue->value]);

            $documentCounts = Document::query()->selectRaw(
                "SUM(CASE WHEN type = ? AND status IN (?, ?) THEN 1 ELSE 0 END) AS quotations,
                 SUM(CASE WHEN type = ? AND status IN (?, ?) THEN 1 ELSE 0 END) AS proformas,
                 SUM(CASE WHEN type = ? AND status NOT IN (?, ?) AND paid_total < total THEN 1 ELSE 0 END) AS invoices,
                 SUM(CASE WHEN type = ? AND status NOT IN (?, ?) AND paid_total < total AND due_at < ? THEN 1 ELSE 0 END) AS overdue",
                [
                    DocumentType::Quotation->value, DocumentStatus::Draft->value, DocumentStatus::Sent->value,
                    DocumentType::Proforma->value, DocumentStatus::Draft->value, DocumentStatus::Sent->value,
                    DocumentType::Invoice->value, DocumentStatus::Draft->value, DocumentStatus::Cancelled->value,
                    DocumentType::Invoice->value, DocumentStatus::Draft->value, DocumentStatus::Cancelled->value, today()->format('Y-m-d'),
                ]
            )->first();

            $counts = [
                'clients' => Client::where('is_active', true)->count(),
                'products' => Product::where('is_active', true)->count(),
                'quotations' => (int) $documentCounts->quotations,
                'proformas' => (int) $documentCounts->proformas,
                'invoices' => (int) $documentCounts->invoices,
                'overdue' => (int) $documentCounts->overdue,
            ];
        }

        $companyLogo = Setting::getValue('logo_path');

        $view->with([
            'navigationCounts' => $counts,
            'companyName' => Setting::getValue('company_name', 'SmartGestion'),
            'companyLogo' => $companyLogo && Storage::disk('public')->exists($companyLogo) ? $companyLogo : null,
        ]);
    }
}