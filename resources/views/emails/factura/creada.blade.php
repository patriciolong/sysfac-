<x-mail::message>
# Nueva Factura Electrónica

Estimado/a {{ $factura->cliente->razon_social }},

Adjunto a este correo encontrará su factura electrónica Nro. **{{ $factura->numero_comprobante }}**, emitida el **{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}** por un valor total de **${{ number_format($factura->importe_total, 2) }}**.

Se han adjuntado a este correo los documentos correspondientes en formato PDF y XML.

Gracias por su preferencia.

<x-mail::button :url="config('app.url')">
Visitar Sistema
</x-mail::button>

Saludos cordiales,<br>
*Creado por el sistema de [vixion-io.com](https://vixion-io.com)*
</x-mail::message>
