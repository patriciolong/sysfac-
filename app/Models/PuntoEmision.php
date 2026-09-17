<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuntoEmision extends Model
{
    protected $table = 'configuracion_puntos_emision';
    public $timestamps = false;

    protected $fillable = [
        'emisor_id',
        'establecimiento',
        'punto_emision',
        'secuencial_factura',
        'secuencial_nota_credito',
        'secuencial_guia_remision',
        'secuencial_retencion',
        'secuencial_liquidacion_compra',
        'estado'
    ];

    public function emisor()
    {
        return $this->belongsTo(Emisor::class, 'emisor_id');
    }

    public function getCodigoCompletoAttribute()
    {
        return "{$this->establecimiento}-{$this->punto_emision}";
    }

    public function getSiguienteSecuencialFacturaFormattedAttribute()
    {
        $sec = str_pad($this->secuencial_factura, 9, '0', STR_PAD_LEFT);
        return "{$this->establecimiento}-{$this->punto_emision}-{$sec}";
    }
}
