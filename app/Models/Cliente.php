<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes_cliente';

    public $timestamps = false;

    protected $fillable = [
        'tipo_identificacion',
        'identificacion',
        'razon_social',
        'direccion',
        'telefono',
        'correo',
        'obligado_contabilidad',
    ];

    public function getTipoNombreAttribute()
    {
        switch ($this->tipo_identificacion) {
            case '04':
                return 'RUC';
            case '05':
                return 'CÉDULA';
            case '06':
                return 'PASAPORTE';
            case '07':
                return 'CONSUMIDOR FINAL';
            case '08':
                return 'ID. EXTERIOR';
            default:
                return 'IDENTIFICACIÓN';
        }
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'cliente_id');
    }
}
