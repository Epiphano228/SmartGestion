<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Setting;
use App\Services\LogoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Document $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->document->type->label()} {$this->document->number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document');
    }

    public function attachments(): array
    {
        $this->document->load(['client', 'items.product', 'payments']);
        $settings = Setting::pluck('value', 'key');
        $settings['pdf_logo_path'] = app(LogoService::class)->pdfPath($settings->get('logo_path'));
        $pdf = Pdf::loadView('pdf.document', ['document' => $this->document, 'settings' => $settings])->output();
        return [Attachment::fromData(fn () => $pdf, $this->document->number.'.pdf')->withMime('application/pdf')];
    }
}
