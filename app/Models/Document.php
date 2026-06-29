<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $guarded = [];
    protected $casts = [
        'type' => DocumentType::class,
        'status' => DocumentStatus::class,
        'issued_at' => 'date',
        'due_at' => 'date',
        'sent_at' => 'datetime',
        'converted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_total' => 'decimal:2',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function source(): BelongsTo { return $this->belongsTo(self::class, 'source_document_id'); }
    public function items(): HasMany { return $this->hasMany(DocumentItem::class)->orderBy('position'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class)->latest('paid_at'); }
    public function convertedDocuments(): HasMany { return $this->hasMany(self::class, 'source_document_id'); }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_total);
    }

    public function scopeInvoices($query) { return $query->where('type', DocumentType::Invoice->value); }
    public function scopeProformas($query) { return $query->where('type', DocumentType::Proforma->value); }
    public function scopeQuotations($query) { return $query->where('type', DocumentType::Quotation->value); }
    public function scopeOffers($query) { return $query->whereIn('type', [DocumentType::Proforma->value, DocumentType::Quotation->value]); }

    /** Invoices that have entered the commercial/accounting cycle. */
    public function scopeIssued($query)
    {
        return $query->whereNotIn('status', [DocumentStatus::Draft->value, DocumentStatus::Cancelled->value]);
    }

    /** Issued invoices with a remaining amount to collect. */
    public function scopeReceivable($query)
    {
        return $query->issued()->whereColumn('paid_total', '<', 'total');
    }

    /** Proformas actually presented to a client, excluding drafts and cancellations. */
    public function scopeSubmitted($query)
    {
        return $query->whereNotIn('status', [DocumentStatus::Draft->value, DocumentStatus::Cancelled->value]);
    }
}