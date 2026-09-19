<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaCredito extends Model
{
    protected $table = 'ventas_notas_credito';

    public $timestamps = false;

    protected $appends = ['numero_comprobante', 'numero_factura_modificada'];

    protected $fillable = [
        'emisor_id',
        'punto_emision_id',
        'cliente_id',
        'usuario_id',
        'factura_modificada_id',
        'ambiente',
        'tipo_emision',
        'codigo_documento',
        'establecimiento',
        'punto_emision',
        'secuencial',
        'clave_acceso',
        'fecha_emision',
        'motivo',
        'tipo_modificacion',
        'fecha_emision_documento_modificado',
        'total_sin_impuestos',
        'base_imponible_0',
        'base_imponible_iva',
        'base_no_objeto',
        'base_exento',
        'valor_iva',
        'valor_ice',
        'valor_modificacion',
        'moneda',
        'estado_sri',
        'fecha_autorizacion',
        'numero_autorizacion',
        'mensajes_sri',
        'xml_generado',
        'created_at',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_emision_documento_modificado' => 'date',
        'fecha_autorizacion' => 'datetime',
        'total_sin_impuestos' => 'decimal:2',
        'base_imponible_0' => 'decimal:2',
        'base_imponible_iva' => 'decimal:2',
        'base_no_objeto' => 'decimal:2',
        'base_exento' => 'decimal:2',
        'valor_iva' => 'decimal:2',
        'valor_modificacion' => 'decimal:2',
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
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_modificada_id');
    }

    public function detalles()
    {
        return $this->hasMany(NotaCreditoDetalle::class, 'nota_credito_id');
    }

    public function getNumeroComprobanteAttribute()
    {
        return "{$this->establecimiento}-{$this->punto_emision}-{$this->secuencial}";
    }

    public function getNumeroFacturaModificadaAttribute()
    {
        if ($this->factura) {
            return $this->factura->numero_comprobante;
        }

        return '';
    }
}
