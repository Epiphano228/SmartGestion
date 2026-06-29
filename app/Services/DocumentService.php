<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function nextNumber(DocumentType $type): string
    {
        $prefix = Setting::getValue($type->prefixSettingKey(), $type->defaultPrefix());
        $digits = (int) Setting::getValue('number_digits', 5);
        $year = (int) now()->format('Y');
        $last = DB::transaction(function () use ($type, $year) {
            $sequence = DocumentSequence::firstOrCreate(
                ['type' => $type->value, 'year' => $year],
                ['last_number' => 0]
            );
            $sequence = DocumentSequence::whereKey($sequence->id)->lockForUpdate()->firstOrFail();
            $sequence->increment('last_number');
            return $sequence->last_number;
        });

        return sprintf('%s-%s-%0'.$digits.'d', $prefix, $year, $last);
    }

    public function recalculate(Document $document): Document
    {
        $items = $document->items;
        $subtotal = $items->sum('line_subtotal');
        $tax = $items->sum('line_tax');
        $document->update([
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => $tax,
            'total' => $subtotal + $tax,
        ]);
        return $document->refresh();
    }

    public function convertToInvoice(Document $sourceDocument): Document
    {
        abort_unless($sourceDocument->type->isOffer(), 422, 'Seul un devis ou un proforma peut être converti.');
        return DB::transaction(function () use ($sourceDocument) {
            if ($existing = $sourceDocument->convertedDocuments()->invoices()->first()) return $existing;
            $invoice = $sourceDocument->replicate(['number', 'status', 'sent_at', 'converted_at']);
            $invoice->fill([
                'type' => DocumentType::Invoice,
                'number' => $this->nextNumber(DocumentType::Invoice),
                'status' => DocumentStatus::Draft,
                'source_document_id' => $sourceDocument->id,
                'created_by' => auth()->id(),
                'paid_total' => 0,
            ])->save();
            foreach ($sourceDocument->items as $item) {
                $invoice->items()->create($item->only([
                    'product_id', 'name', 'description', 'quantity', 'unit_price', 'tax_rate',
                    'line_subtotal', 'line_tax', 'line_total', 'position',
                ]));
            }
            $sourceDocument->update(['status' => DocumentStatus::Accepted, 'converted_at' => now()]);
            ActivityLog::record('converted', "{$sourceDocument->number} converti en {$invoice->number}", $invoice);
            return $invoice;
        });
    }

    public function syncPaymentStatus(Document $invoice): void
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $status = match (true) {
            $paid >= (float) $invoice->total => DocumentStatus::Paid,
            $invoice->due_at?->isPast() && $paid < (float) $invoice->total => DocumentStatus::Overdue,
            $paid > 0 => DocumentStatus::Partial,
            default => DocumentStatus::Sent,
        };
        $invoice->update(['paid_total' => $paid, 'status' => $status]);
    }
}