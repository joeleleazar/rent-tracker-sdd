<?php

namespace App\Mail;

use App\Models\Contrato;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContratoProximoAVencer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Contrato $contrato,
        public readonly int $diasAnticipacion,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Contrato próximo a vencer (%d días) — %s', $this->diasAnticipacion, $this->contrato->locacion->nombre),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contrato-proximo-a-vencer',
            with: [
                'contrato' => $this->contrato,
                'diasAnticipacion' => $this->diasAnticipacion,
            ],
        );
    }
}
