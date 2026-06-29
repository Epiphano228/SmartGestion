<?php

namespace App\Livewire\Reports;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\Document;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    #[Url(except: '12')]
    public string $period = '12';

    public function render()
    {
        $months = in_array((int) $this->period, [3, 6, 12, 24], true) ? (int) $this->period : 12;
        $from = now()->subMonths($months - 1)->startOfMonth();
        $invoices = Document::with('client')->invoices()
            ->where('issued_at', '>=', $from)
            ->issued()
            ->get();
        $offers = Document::offers()
            ->where('issued_at', '>=', $from)
            ->issued()
            ->get();

        $turnover = (float) $invoices->sum('total');
        $collected = (float) $invoices->sum('paid_total');
        $outstanding = max(0, $turnover - $collected);
        $converted = $offers->whereNotNull('converted_at')->count();
        $conversionRate = $offers->count() ? round($converted / $offers->count() * 100, 1) : 0;
        $collectionRate = $turnover > 0 ? round($collected / $turnover * 100, 1) : 0;

        $topClients = $invoices->groupBy('client_id')
            ->map(fn ($rows) => ['client' => $rows->first()->client, 'total' => (float) $rows->sum('total'), 'count' => $rows->count()])
            ->sortByDesc('total')->take(5)->values();

        $grouped = $invoices->groupBy(fn ($document) => $document->issued_at->format('Y-m'))->map(fn ($rows) => (float) $rows->sum('total'));
        $monthly = collect(range($months - 1, 0))->map(function ($offset) use ($grouped) {
            $date = now()->subMonths($offset);
            return ['key' => $date->format('Y-m'), 'label' => $date->translatedFormat('M y'), 'full' => $date->translatedFormat('F Y'), 'value' => $grouped->get($date->format('Y-m'), 0)];
        });

        return view('livewire.reports.index', [
            'turnover' => $turnover,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'conversionRate' => $conversionRate,
            'collectionRate' => $collectionRate,
            'averageInvoice' => $invoices->count() ? $turnover / $invoices->count() : 0,
            'activeClients' => Client::whereHas('documents', fn ($query) => $query->where('issued_at', '>=', $from))->count(),
            'overdueCount' => $invoices->filter(fn ($document) => $document->due_at?->isPast() && $document->balance > 0)->count(),
            'invoiceCount' => $invoices->count(),
            'topClients' => $topClients,
            'monthly' => $monthly,
            'currency' => Setting::getValue('currency', 'XOF'),
        ])->title('Statistiques');
    }
}