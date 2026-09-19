<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'configuracion_metodos_pago';

    public $timestamps = false;

    protected $fillable = [
        'codigo_sri',
        'nombre',
        'estado',
    ];

    public function getIconoAttribute()
    {
        switch ($this->codigo_sri) {
            case '01':
                return 'fa-money-bill-wave';
            case '16':
                return 'fa-credit-card';
            case '19':
                return 'fa-credit-card';
            case '20':
                return 'fa-mobile-screen-button';
            case '17':
                return 'fa-wallet';
            default:
                return 'fa-coins';
        }
    }
}
