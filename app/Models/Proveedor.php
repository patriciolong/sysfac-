<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'compras_proveedores';

    public $timestamps = false;

    protected $fillable = [
        'tipo_identificacion',
        'identificacion',
        'razon_social',
        'direccion',
        'telefono',
        'correo',
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }

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
}
