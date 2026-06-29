<?php

namespace App\Livewire;

use App\Enums\DocumentStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Document;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $currentStart = now()->startOfMonth()->format('Y-m-d');
        $currentEnd = now()->endOfMonth()->format('Y-m-d');
        $previousStart = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $previousEnd = now()->subMonth()->endOfMonth()->format('Y-m-d');

        $invoiceMetrics = Document::invoices()->issued()->selectRaw(
            "COALESCE(SUM(total), 0) AS turnover,
             COALESCE(SUM(paid_total), 0) AS collected,
             SUM(CASE WHEN due_at < ? AND paid_total < total THEN 1 ELSE 0 END) AS overdue_count,
             COALESCE(SUM(CASE WHEN issued_at BETWEEN ? AND ? THEN total ELSE 0 END), 0) AS current_month,
             COALESCE(SUM(CASE WHEN issued_at BETWEEN ? AND ? THEN total ELSE 0 END), 0) AS previous_month,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS paid_count,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS partial_count",
            [today()->format('Y-m-d'), $currentStart, $currentEnd, $previousStart, $previousEnd, DocumentStatus::Paid->value, DocumentStatus::Partial->value]
        )->first();

        $turnover = (float) $invoiceMetrics->turnover;
        $collected = (float) $invoiceMetrics->collected;
        $outstanding = max(0, $turnover - $collected);
        $overdueCount = (int) $invoiceMetrics->overdue_count;
        $currentMonth = (float) $invoiceMetrics->current_month;
        $previousMonth = (float) $invoiceMetrics->previous_month;
        $trend = $previousMonth > 0 ? round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1) : null;

        $pendingOffers = Document::offers()
            ->whereIn('status', [DocumentStatus::Draft->value, DocumentStatus::Sent->value])
            ->count();
        $newClients = Client::where('created_at', '>=', now()->startOfMonth())->count();

        $monthly = Document::invoices()
            ->where('issued_at', '>=', now()->subMonths(11)->startOfMonth())
            ->issued()->orderBy('issued_at')->get(['issued_at', 'total'])
            ->groupBy(fn ($document) => $document->issued_at->format('Y-m'))
            ->map(fn ($documents) => (float) $documents->sum('total'));

        $chart = collect(range(11, 0))->map(function ($offset) use ($monthly) {
            $date = now()->subMonths($offset);
            return ['label' => $date->translatedFormat('M'), 'full' => $date->translatedFormat('F Y'), 'value' => $monthly->get($date->format('Y-m'), 0)];
        });

        return view('livewire.dashboard', [
            'metrics' => compact('turnover', 'collected', 'outstanding', 'pendingOffers', 'newClients', 'overdueCount', 'currentMonth', 'trend'),
            'chart' => $chart,
            'paymentHealth' => ['paid' => (int) $invoiceMetrics->paid_count, 'partial' => (int) $invoiceMetrics->partial_count, 'overdue' => $overdueCount],
            'recentDocuments' => Document::with(['client', 'source'])->latest('issued_at')->limit(7)->get(),
            'activities' => ActivityLog::with('user')->latest()->limit(7)->get(),
            'currency' => Setting::getValue('currency', 'XOF'),
        ])->title('Tableau de bord');
    }
}