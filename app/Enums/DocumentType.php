<?php

namespace App\Enums;

enum DocumentType: string
{
    case Proforma = 'proforma';
    case Quotation = 'quotation';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Proforma => 'Proforma',
            self::Quotation => 'Devis',
            self::Invoice => 'Facture',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Proforma => 'Proformas',
            self::Quotation => 'Devis',
            self::Invoice => 'Factures',
        };
    }

    public function newLabel(): string
    {
        return match ($this) {
            self::Proforma => 'Nouvelle proforma',
            self::Quotation => 'Nouveau devis',
            self::Invoice => 'Nouvelle facture',
        };
    }

    public function prefixSettingKey(): string
    {
        return match ($this) {
            self::Proforma => 'proforma_prefix',
            self::Quotation => 'quotation_prefix',
            self::Invoice => 'invoice_prefix',
        };
    }

    public function defaultPrefix(): string
    {
        return match ($this) {
            self::Proforma => 'PRO',
            self::Quotation => 'DEV',
            self::Invoice => 'FAC',
        };
    }

    public function isOffer(): bool
    {
        return $this !== self::Invoice;
    }
}