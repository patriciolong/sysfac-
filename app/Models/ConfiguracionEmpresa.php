<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionEmpresa extends Model
{
    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion_matriz',
        'regimen_rimpe',
        'firma_ruta',
        'firma_clave',
        'ambiente_sri'
    ];
}
