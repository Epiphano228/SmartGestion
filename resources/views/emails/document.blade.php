<div style="font-family:Arial,sans-serif;max-width:620px;margin:auto;color:#172033">
    <h2>{{ $document->type->label() }} {{ $document->number }}</h2>
    <p>Bonjour,</p>
    <p>Veuillez trouver en pièce jointe votre {{ strtolower($document->type->label()) }} d’un montant de <strong>{{ number_format($document->total,2,',',' ') }} {{ $document->currency }}</strong>.</p>
    <p>Nous restons à votre disposition pour toute question.</p>
    <p style="margin-top:35px;color:#64748b">Cordialement,<br>{{ \App\Models\Setting::getValue('company_name','SmartGestion') }}</p>
</div>
