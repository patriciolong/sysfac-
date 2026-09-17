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
        'correo'
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }
}
