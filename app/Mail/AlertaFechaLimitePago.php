<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AlertaFechaLimitePago extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Carbon $fechaLimite,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Fecha límite de pago próxima: %s', $this->fechaLimite->translatedFormat('d/m/Y')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.alerta-fecha-limite-pago',
            with: [
                'fechaLimite' => $this->fechaLimite,
            ],
        );
    }
}
