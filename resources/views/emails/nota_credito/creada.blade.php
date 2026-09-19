<x-mail::message>
# Nota de Crédito Electrónica

Estimado/a {{ $notaCredito->cliente->razon_social }},

Adjunto a este correo encontrará su Nota de Crédito electrónica Nro. **{{ $notaCredito->numero_comprobante }}**, emitida el **{{ \Carbon\Carbon::parse($notaCredito->fecha_emision)->format('d/m/Y') }}** afectando a la Factura **{{ $notaCredito->numero_factura_modificada }}** por un valor total de modificación de **${{ number_format($notaCredito->valor_modificacion, 2) }}**.

**Motivo:** {{ $notaCredito->motivo }}

Se han adjuntado a este correo los documentos correspondientes en formato PDF (RIDE) y XML autorizado por el SRI.

<x-mail::button :url="config('app.url')">
Visitar Sistema
</x-mail::button>

Saludos cordiales,<br>
*Creado por el sistema de [vixion-io.com](https://vixion-io.com)*
</x-mail::message>
