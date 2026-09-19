<?php

namespace App\Mail;

use App\Models\NotaCredito;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotaCreditoCreada extends Mailable
{
    use Queueable, SerializesModels;

    public NotaCredito $notaCredito;

    /**
     * Create a new message instance.
     */
    public function __construct(NotaCredito $notaCredito)
    {
        $this->notaCredito = $notaCredito;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nota de Crédito Electrónica Nro. '.$this->notaCredito->numero_comprobante,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nota_credito.creada',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        // Generar PDF
        $notaCredito = NotaCredito::with(['cliente', 'detalles.producto', 'factura', 'emisor'])->findOrFail($this->notaCredito->id);
        $pdf = Pdf::loadView('notas_credito.pdf', compact('notaCredito'));

        $attachments = [
            Attachment::fromData(fn () => $pdf->output(), 'Nota_Credito_'.$this->notaCredito->numero_comprobante.'.pdf')
                ->withMime('application/pdf'),
        ];

        // Adjuntar XML si existe
        if (! empty($this->notaCredito->xml_generado)) {
            $attachments[] = Attachment::fromData(fn () => $this->notaCredito->xml_generado, 'Nota_Credito_'.$this->notaCredito->numero_comprobante.'.xml')
                ->withMime('application/xml');
        }

        return $attachments;
    }
}
