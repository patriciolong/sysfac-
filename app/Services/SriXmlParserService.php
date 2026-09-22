<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use Exception;
use SimpleXMLElement;

class SriXmlParserService
{
    /**
     * Parsea un archivo XML de Factura electrónica del SRI (Ecuador).
     *
     * @throws Exception
     */
    public function parseFacturaXml(string $xmlContent): array
    {
        $xml = $this->loadXml($xmlContent);

        // Identificar nodo raíz o comprobante interno
        $rootName = $xml->getName();
        $facturaNode = null;

        if ($rootName === 'factura') {
            $facturaNode = $xml;
        } elseif ($rootName === 'autorizacion') {
            $comprobante = (string) $xml->comprobante;
            if (! empty($comprobante)) {
                $facturaNode = $this->loadXml($comprobante);
            }
        }

        if (! $facturaNode || $facturaNode->getName() !== 'factura') {
            throw new Exception('El archivo XML no corresponde a una Factura Electrónica válida del SRI.');
        }

        // 1. infoTributaria
        $infoTrib = $facturaNode->infoTributaria ?? null;
        if (! $infoTrib) {
            throw new Exception('El XML no contiene la sección obligatoria <infoTributaria>.');
        }

        $ruc = trim((string) ($infoTrib->ruc ?? ''));
        $razonSocial = trim((string) ($infoTrib->razonSocial ?? ''));
        $nombreComercial = trim((string) ($infoTrib->nombreComercial ?? ''));
        $estab = str_pad(trim((string) ($infoTrib->estab ?? '001')), 3, '0', STR_PAD_LEFT);
        $ptoEmi = str_pad(trim((string) ($infoTrib->ptoEmi ?? '001')), 3, '0', STR_PAD_LEFT);
        $secuencial = str_pad(trim((string) ($infoTrib->secuencial ?? '000000001')), 9, '0', STR_PAD_LEFT);
        $numeroFactura = "{$estab}-{$ptoEmi}-{$secuencial}";
        $claveAcceso = trim((string) ($infoTrib->claveAcceso ?? ''));
        $dirMatriz = trim((string) ($infoTrib->dirMatriz ?? ''));

        // 2. infoFactura
        $infoFact = $facturaNode->infoFactura ?? null;
        if (! $infoFact) {
            throw new Exception('El XML no contiene la sección obligatoria <infoFactura>.');
        }

        $fechaEmisionRaw = trim((string) ($infoFact->fechaEmision ?? ''));
        $fechaEmision = $this->normalizarFecha($fechaEmisionRaw);
        $dirEstablecimiento = trim((string) ($infoFact->dirEstablecimiento ?? $dirMatriz));
        $subtotalSinImpuestos = (float) ($infoFact->totalSinImpuestos ?? 0);
        $totalDescuento = (float) ($infoFact->totalDescuento ?? 0);
        $importeTotal = (float) ($infoFact->importeTotal ?? 0);

        // 3. infoAdicional (Contactos opcionales)
        $adicionales = [];
        if (isset($facturaNode->infoAdicional)) {
            foreach ($facturaNode->infoAdicional->campoAdicional as $campo) {
                $nombreCampo = strtolower(trim((string) ($campo['nombre'] ?? '')));
                $valorCampo = trim((string) $campo);
                $adicionales[$nombreCampo] = $valorCampo;
            }
        }

        $correo = $this->extraerDeAdicionales($adicionales, ['email', 'correo', 'mail', 'e-mail']);
        $telefono = $this->extraerDeAdicionales($adicionales, ['telefono', 'tel', 'celular', 'telf', 'telefono1', 'telefono2']);
        $direccion = $this->extraerDeAdicionales($adicionales, ['direccion', 'dir', 'domicilio']) ?: $dirEstablecimiento ?: $dirMatriz;

        // 4. Buscar o preparar Proveedor en Base de Datos
        $proveedorDb = Proveedor::where('identificacion', $ruc)->first();
        $tipoIdentificacion = strlen($ruc) === 13 ? '04' : '05';

        $proveedorInfo = [
            'exists' => (bool) $proveedorDb,
            'id' => $proveedorDb ? $proveedorDb->id : null,
            'identificacion' => $ruc,
            'tipo_identificacion' => $proveedorDb ? $proveedorDb->tipo_identificacion : $tipoIdentificacion,
            'razon_social' => $proveedorDb ? $proveedorDb->razon_social : ($razonSocial ?: $nombreComercial),
            'direccion' => $proveedorDb ? $proveedorDb->direccion : $direccion,
            'telefono' => $proveedorDb ? $proveedorDb->telefono : $telefono,
            'correo' => $proveedorDb ? $proveedorDb->correo : ($correo ?: 'proveedor@factura.com'),
            'display' => $proveedorDb
                ? "{$proveedorDb->razon_social} ({$proveedorDb->identificacion})"
                : ($razonSocial ? "{$razonSocial} ({$ruc})" : "Proveedor ({$ruc})"),
        ];

        // 5. Detalles de productos
        $detalles = [];
        $totalIvaCalculado = 0.0;

        if (isset($facturaNode->detalles->detalle)) {
            foreach ($facturaNode->detalles->detalle as $d) {
                $codigoPrincipal = trim((string) ($d->codigoPrincipal ?? $d->codigoInterno ?? ''));
                $codigoAuxiliar = trim((string) ($d->codigoAuxiliar ?? ''));
                $descripcion = trim((string) ($d->descripcion ?? ''));
                $cantidad = (float) ($d->cantidad ?? 1);
                $precioUnitario = (float) ($d->precioUnitario ?? 0);
                $descuento = (float) ($d->descuento ?? 0);
                $precioTotalSinImpuesto = (float) ($d->precioTotalSinImpuesto ?? ($cantidad * $precioUnitario - $descuento));

                // Extraer porcentaje / tarifa de IVA
                $tarifaIva = 0.0;
                $codigoIvaSri = '2'; // Por defecto 15% IVA tarifa general
                if (isset($d->impuestos->impuesto)) {
                    foreach ($d->impuestos->impuesto as $imp) {
                        $codigoImp = (string) ($imp->codigo ?? '');
                        if ($codigoImp === '2') { // 2 = IVA
                            $tarifaAttr = (float) ($imp->tarifa ?? -1);
                            $codPorcentaje = (string) ($imp->codigoPorcentaje ?? '');

                            if ($tarifaAttr >= 0) {
                                $tarifaIva = $tarifaAttr;
                            } else {
                                $tarifaIva = $this->tarifaIvaPorCodigoPorcentaje($codPorcentaje);
                            }

                            if ($tarifaIva > 0) {
                                $codigoIvaSri = ($tarifaIva == 5.0) ? '4' : '2';
                            } else {
                                $codigoIvaSri = '0';
                            }
                        }
                    }
                }

                $ivaLinea = round($precioTotalSinImpuesto * ($tarifaIva / 100), 2);
                $totalIvaCalculado += $ivaLinea;

                // Buscar coincidencia en catálogo de productos
                $productoDb = null;
                if (! empty($codigoPrincipal)) {
                    $productoDb = Producto::where('codigo_principal', $codigoPrincipal)->first();
                }
                if (! $productoDb && ! empty($codigoAuxiliar)) {
                    $productoDb = Producto::where('codigo_principal', $codigoAuxiliar)
                        ->orWhere('codigo_auxiliar', $codigoAuxiliar)
                        ->first();
                }
                if (! $productoDb && ! empty($descripcion)) {
                    $productoDb = Producto::where('nombre', $descripcion)
                        ->orWhere('nombre', 'LIKE', "%{$descripcion}%")
                        ->first();
                }

                $detalles[] = [
                    'codigo' => $codigoPrincipal ?: ($codigoAuxiliar ?: 'S/C'),
                    'codigo_auxiliar' => $codigoAuxiliar,
                    'descripcion' => $descripcion,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $precioUnitario,
                    'descuento' => $descuento,
                    'subtotal' => $precioTotalSinImpuesto,
                    'tarifa_iva' => $tarifaIva,
                    'iva_linea' => $ivaLinea,
                    'codigo_iva' => $codigoIvaSri,
                    'matched_producto' => $productoDb ? [
                        'id' => $productoDb->id,
                        'nombre' => $productoDb->nombre,
                        'codigo' => $productoDb->codigo_principal,
                        'costo_promedio' => (float) ($productoDb->costo_promedio ?? 0),
                        'tarifa_iva' => $productoDb->tarifa_iva_porcentaje,
                        'codigo_iva' => $productoDb->codigo_iva,
                    ] : null,
                ];
            }
        }

        $totalCalculado = round($subtotalSinImpuestos + $totalIvaCalculado, 2);

        return [
            'tipo_comprobante' => 'FACTURA',
            'proveedor' => $proveedorInfo,
            'factura' => [
                'numero_factura' => $numeroFactura,
                'fecha_emision' => $fechaEmision,
                'clave_acceso' => $claveAcceso,
                'subtotal_sin_impuestos' => $subtotalSinImpuestos,
                'total_descuento' => $totalDescuento,
                'total_iva' => $totalIvaCalculado,
                'total' => $importeTotal > 0 ? $importeTotal : $totalCalculado,
                'observaciones' => "Importado desde Factura Electrónica SRI #{$numeroFactura} - {$razonSocial}",
            ],
            'detalles' => $detalles,
        ];
    }

    /**
     * Parsea un archivo XML de Nota de Crédito electrónica del SRI (Ecuador).
     *
     * @throws Exception
     */
    public function parseNotaCreditoXml(string $xmlContent): array
    {
        $xml = $this->loadXml($xmlContent);

        $rootName = $xml->getName();
        $ncNode = null;

        if ($rootName === 'notaCredito') {
            $ncNode = $xml;
        } elseif ($rootName === 'autorizacion') {
            $comprobante = (string) $xml->comprobante;
            if (! empty($comprobante)) {
                $ncNode = $this->loadXml($comprobante);
            }
        }

        if (! $ncNode || $ncNode->getName() !== 'notaCredito') {
            throw new Exception('El archivo XML no corresponde a una Nota de Crédito válida del SRI.');
        }

        // 1. infoTributaria
        $infoTrib = $ncNode->infoTributaria ?? null;
        if (! $infoTrib) {
            throw new Exception('El XML no contiene la sección obligatoria <infoTributaria>.');
        }

        $ruc = trim((string) ($infoTrib->ruc ?? ''));
        $razonSocial = trim((string) ($infoTrib->razonSocial ?? ''));
        $estab = str_pad(trim((string) ($infoTrib->estab ?? '001')), 3, '0', STR_PAD_LEFT);
        $ptoEmi = str_pad(trim((string) ($infoTrib->ptoEmi ?? '001')), 3, '0', STR_PAD_LEFT);
        $secuencial = str_pad(trim((string) ($infoTrib->secuencial ?? '000000001')), 9, '0', STR_PAD_LEFT);
        $numeroNotaCredito = "{$estab}-{$ptoEmi}-{$secuencial}";
        $claveAcceso = trim((string) ($infoTrib->claveAcceso ?? ''));

        // 2. infoNotaCredito
        $infoNc = $ncNode->infoNotaCredito ?? null;
        if (! $infoNc) {
            throw new Exception('El XML no contiene la sección obligatoria <infoNotaCredito>.');
        }

        $fechaEmisionRaw = trim((string) ($infoNc->fechaEmision ?? ''));
        $fechaEmision = $this->normalizarFecha($fechaEmisionRaw);
        $numDocModificadoRaw = trim((string) ($infoNc->numDocModificado ?? ''));
        $numDocModificado = $this->normalizarNumeroFactura($numDocModificadoRaw);
        $motivo = trim((string) ($infoNc->motivo ?? 'Devolución / Descuento según Nota de Crédito'));
        $valorModificacion = (float) ($infoNc->valorModificacion ?? 0);
        $subtotalSinImpuestos = (float) ($infoNc->totalSinImpuestos ?? 0);

        // 3. Buscar Proveedor en BD
        $proveedorDb = Proveedor::where('identificacion', $ruc)->first();

        // 4. Buscar Factura de Compra Modificada en BD
        $compraDb = null;
        if (! empty($numDocModificado)) {
            $compraDb = Compra::with(['proveedor', 'bodega', 'detalles.producto'])
                ->where('numero_factura', $numDocModificado)
                ->orWhere('numero_factura', $numDocModificadoRaw)
                ->first();

            // Búsqueda flexible por secuencial si no se encuentra exacto
            if (! $compraDb) {
                $compraDb = Compra::with(['proveedor', 'bodega', 'detalles.producto'])
                    ->where('numero_factura', 'LIKE', "%{$secuencial}%")
                    ->first();
            }
        }

        // 5. Detalles de productos en la NC
        $detalles = [];
        $totalIvaCalculado = 0.0;

        if (isset($ncNode->detalles->detalle)) {
            foreach ($ncNode->detalles->detalle as $d) {
                $codigoPrincipal = trim((string) ($d->codigoInterno ?? $d->codigoPrincipal ?? ''));
                $codigoAdicional = trim((string) ($d->codigoAdicional ?? ''));
                $descripcion = trim((string) ($d->descripcion ?? ''));
                $cantidad = (float) ($d->cantidad ?? 0);
                $precioUnitario = (float) ($d->precioUnitario ?? 0);
                $descuento = (float) ($d->descuento ?? 0);
                $precioTotalSinImpuesto = (float) ($d->precioTotalSinImpuesto ?? ($cantidad * $precioUnitario - $descuento));

                $tarifaIva = 0.0;
                if (isset($d->impuestos->impuesto)) {
                    foreach ($d->impuestos->impuesto as $imp) {
                        if ((string) ($imp->codigo ?? '') === '2') {
                            $tarifaAttr = (float) ($imp->tarifa ?? -1);
                            $codPorcentaje = (string) ($imp->codigoPorcentaje ?? '');
                            $tarifaIva = ($tarifaAttr >= 0) ? $tarifaAttr : $this->tarifaIvaPorCodigoPorcentaje($codPorcentaje);
                        }
                    }
                }

                $ivaLinea = round($precioTotalSinImpuesto * ($tarifaIva / 100), 2);
                $totalIvaCalculado += $ivaLinea;

                // Buscar producto en la compra encontrada o en catálogo
                $productoDb = null;
                if ($compraDb) {
                    $compraDetalle = $compraDb->detalles->first(function ($cd) use ($codigoPrincipal, $descripcion) {
                        return $cd->producto && ($cd->producto->codigo_principal === $codigoPrincipal || $cd->producto->nombre === $descripcion);
                    });
                    if ($compraDetalle) {
                        $productoDb = $compraDetalle->producto;
                    }
                }

                if (! $productoDb && ! empty($codigoPrincipal)) {
                    $productoDb = Producto::where('codigo_principal', $codigoPrincipal)->first();
                }

                $detalles[] = [
                    'codigo' => $codigoPrincipal ?: ($codigoAdicional ?: 'S/C'),
                    'descripcion' => $descripcion,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $precioUnitario,
                    'subtotal' => $precioTotalSinImpuesto,
                    'tarifa_iva' => $tarifaIva,
                    'iva_linea' => $ivaLinea,
                    'matched_producto_id' => $productoDb ? $productoDb->id : null,
                    'matched_producto_nombre' => $productoDb ? $productoDb->nombre : null,
                ];
            }
        }

        return [
            'tipo_comprobante' => 'NOTA_CREDITO',
            'nota_credito' => [
                'numero_nota_credito' => $numeroNotaCredito,
                'autorizacion_sri' => $claveAcceso,
                'fecha_emision' => $fechaEmision,
                'num_doc_modificado' => $numDocModificado ?: $numDocModificadoRaw,
                'motivo' => $motivo,
                'subtotal' => $subtotalSinImpuestos,
                'iva' => $totalIvaCalculado,
                'total' => $valorModificacion > 0 ? $valorModificacion : round($subtotalSinImpuestos + $totalIvaCalculado, 2),
                'observaciones' => "Nota de crédito importada desde XML SRI #{$numeroNotaCredito}",
            ],
            'proveedor' => [
                'exists' => (bool) $proveedorDb,
                'id' => $proveedorDb ? $proveedorDb->id : null,
                'identificacion' => $ruc,
                'razon_social' => $proveedorDb ? $proveedorDb->razon_social : $razonSocial,
            ],
            'compra_modificada' => $compraDb ? [
                'found' => true,
                'id' => $compraDb->id,
                'numero_factura' => $compraDb->numero_factura,
                'fecha_emision' => $compraDb->fecha_emision,
                'total' => (float) $compraDb->total,
                'saldo_pendiente' => $compraDb->saldo_pendiente,
                'proveedor_id' => $compraDb->proveedor_id,
                'proveedor_nombre' => $compraDb->proveedor->razon_social ?? 'N/A',
                'bodega_id' => $compraDb->bodega_id,
                'bodega_nombre' => $compraDb->bodega->nombre ?? 'N/A',
            ] : [
                'found' => false,
                'numero_factura' => $numDocModificado ?: $numDocModificadoRaw,
            ],
            'detalles' => $detalles,
        ];
    }

    /**
     * Carga y parsea la cadena XML de forma segura.
     *
     * @throws Exception
     */
    protected function loadXml(string $xmlContent): SimpleXMLElement
    {
        $cleanContent = trim($xmlContent);

        // Remover posibles BOMs
        $cleanContent = preg_replace('/^[\xEF\xBB\xBF\xFE\xFF\xFF\xFE]+/', '', $cleanContent);

        // Desactivar entity loader si existe (seguridad XML)
        if (\PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader(true);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($cleanContent, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $msg = ! empty($errors) ? $errors[0]->message : 'Estructura XML inválida';
            throw new Exception('Error al procesar el archivo XML: '.trim($msg));
        }

        return $xml;
    }

    /**
     * Normaliza fechas de comprobantes del SRI (DD/MM/YYYY a YYYY-MM-DD).
     */
    protected function normalizarFecha(string $fecha): string
    {
        $fecha = trim($fecha);
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $fecha, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $anio = $matches[3];

            return "{$anio}-{$mes}-{$dia}";
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }

        return date('Y-m-d');
    }

    /**
     * Normaliza números de factura a formato estándar 001-001-000000001.
     */
    protected function normalizarNumeroFactura(string $num): string
    {
        $num = trim($num);
        if (preg_match('/^(\d{3})-?(\d{3})-?(\d{9})$/', $num, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }
        if (preg_match('/^(\d{3})(\d{3})(\d{9})$/', $num, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        return $num;
    }

    /**
     * Extrae un valor de campos adicionales buscando por nombres sinónimos.
     */
    protected function extraerDeAdicionales(array $adicionales, array $claves): ?string
    {
        foreach ($claves as $clave) {
            if (isset($adicionales[$clave]) && ! empty($adicionales[$clave])) {
                return $adicionales[$clave];
            }
        }

        return null;
    }

    /**
     * Devuelve el porcentaje de IVA a partir del código de porcentaje SRI.
     */
    protected function tarifaIvaPorCodigoPorcentaje(string $codigo): float
    {
        switch (trim($codigo)) {
            case '0':
                return 0.0;
            case '2':
                return 15.0; // Tarifa vigente general Ecuador 2024-2026
            case '3':
                return 14.0;
            case '4':
                return 15.0;
            case '5':
                return 5.0;  // Materiales de construcción / tarifa 5%
            case '10':
                return 13.0;
            default:
                return 15.0;
        }
    }
}
