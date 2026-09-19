<?php

namespace App\Models;

use App\Observers\FacturaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([FacturaObserver::class])]
class Factura extends Model
{
    protected $table = 'ventas_facturas';

    protected $appends = ['numero_comprobante'];

    protected $fillable = [
        'emisor_id',
        'punto_emision_id',
        'cliente_id',
        'usuario_id',
        'ambiente',
        'tipo_emision',
        'codigo_documento',
        'establecimiento',
        'punto_emision',
        'secuencial',
        'clave_acceso',
        'fecha_emision',
        'total_sin_impuestos',
        'total_descuento',
        'base_imponible_0',
        'base_imponible_iva',
        'base_no_objeto',
        'base_exento',
        'valor_iva',
        'valor_ice',
        'valor_irbpnr',
        'propina',
        'importe_total',
        'moneda',
        'guia_remision',
        'estado_sri',
        'fecha_autorizacion',
        'numero_autorizacion',
        'mensajes_sri',
        'xml_generado',
    ];

    public function emisor()
    {
        return $this->belongsTo(Emisor::class, 'emisor_id');
    }

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class, 'punto_emision_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class, 'factura_id');
    }

    public function pagos()
    {
        return $this->hasMany(FacturaPago::class, 'factura_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(NotaCredito::class, 'factura_modificada_id');
    }

    public function getNumeroComprobanteAttribute()
    {
        return "{$this->establecimiento}-{$this->punto_emision}-{$this->secuencial}";
    }
}
