<?php

namespace App\Observers;

use App\Mail\FacturaCreada;
use App\Models\Factura;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FacturaObserver
{
    /**
     * Handle the Factura "created" event.
     */
    public function created(Factura $factura): void
    {
        try {
            // Check if the client has an email
            $cliente = $factura->cliente;
            if ($cliente && ! empty($cliente->correo)) {
                // Send email
                Mail::to($cliente->correo)->send(new FacturaCreada($factura));
            }
        } catch (\Exception $e) {
            Log::error('Error sending invoice email: '.$e->getMessage());
        }
    }

    /**
     * Handle the Factura "updated" event.
     */
    public function updated(Factura $factura): void
    {
        // Si quieres que también se envíe cuando pasa a estar AUTORIZADO si antes no lo estaba
        // Puedes descomentar este bloque si es necesario.
        /*
        if ($factura->wasChanged('estado_sri') && $factura->estado_sri === 'AUTORIZADO') {
            try {
                $cliente = $factura->cliente;
                if ($cliente && !empty($cliente->correo)) {
                    Mail::to($cliente->correo)->send(new FacturaCreada($factura));
                }
            } catch (\Exception $e) {
                Log::error('Error sending invoice email on update: ' . $e->getMessage());
            }
        }
        */
    }

    /**
     * Handle the Factura "deleted" event.
     */
    public function deleted(Factura $factura): void
    {
        //
    }

    /**
     * Handle the Factura "restored" event.
     */
    public function restored(Factura $factura): void
    {
        //
    }

    /**
     * Handle the Factura "force deleted" event.
     */
    public function forceDeleted(Factura $factura): void
    {
        //
    }
}
