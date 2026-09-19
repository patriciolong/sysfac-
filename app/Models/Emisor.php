<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emisor extends Model
{
    protected $table = 'configuracion_emisor';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion_matriz',
        'direccion_establecimiento',
        'contribuyente_especial',
        'obligado_contabilidad',
        'agente_retencion',
        'regimen_rimpe',
        'firma_electronica_ruta',
        'firma_electronica_clave',
        'ambiente_sri',
        'proveedor_sistema_ruc',
        'moneda',
    ];

    public function puntosEmision()
    {
        return $this->hasMany(PuntoEmision::class, 'emisor_id');
    }
}
