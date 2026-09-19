<?php

namespace App\Mail;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FacturaCreada extends Mailable
{
    use Queueable, SerializesModels;

    public Factura $factura;

    /**
     * Create a new message instance.
     */
    public function __construct(Factura $factura)
    {
        $this->factura = $factura;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura Electrónica Nro. '.$this->factura->numero_comprobante,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.factura.creada',
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
        $factura = Factura::with(['cliente', 'detalles.producto', 'pagos.metodoPago', 'emisor'])->findOrFail($this->factura->id);
        $pdf = Pdf::loadView('facturacion.pdf', compact('factura'));

        $attachments = [
            Attachment::fromData(fn () => $pdf->output(), 'Factura_'.$this->factura->numero_comprobante.'.pdf')
                ->withMime('application/pdf'),
        ];

        // Adjuntar XML si existe
        if (! empty($this->factura->xml_generado)) {
            $attachments[] = Attachment::fromData(fn () => $this->factura->xml_generado, 'Factura_'.$this->factura->numero_comprobante.'.xml')
                ->withMime('application/xml');
        }

        return $attachments;
    }
}
