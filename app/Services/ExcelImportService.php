<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Producto;
use Exception;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ExcelImportService
{
    /**
     * Procesa un archivo Excel/CSV para el detalle de compras.
     *
     * @throws Exception
     */
    public function parseDetallesCompra(UploadedFile $file): array
    {
        $rows = $this->extractRowsFromFile($file);
        if (empty($rows)) {
            throw new Exception('El archivo cargado está vacío o no contiene filas válidas.');
        }

        $headerInfo = $this->detectHeaders($rows);
        $dataRows = array_slice($rows, $headerInfo['header_row_index'] + 1);

        $detalles = [];
        $unmappedCount = 0;
        $subtotalSinImpuestos = 0.0;
        $totalIva = 0.0;

        foreach ($dataRows as $row) {
            $codigo = trim((string) ($row[$headerInfo['indices']['codigo']] ?? ''));
            $nombre = trim((string) ($row[$headerInfo['indices']['nombre']] ?? ''));
            $cantRaw = $row[$headerInfo['indices']['cantidad']] ?? '1';
            $costoRaw = $row[$headerInfo['indices']['costo']] ?? '0';

            $cantidad = $this->limpiarNumero($cantRaw);
            $costoUnitario = $this->limpiarNumero($costoRaw);

            if (empty($codigo) && empty($nombre)) {
                continue; // Fila vacía
            }

            if ($cantidad <= 0) {
                $cantidad = 1.0;
            }

            // Buscar producto en catálogo
            $productoDb = null;
            if (! empty($codigo)) {
                $productoDb = Producto::where('codigo_principal', $codigo)
                    ->orWhere('codigo_auxiliar', $codigo)
                    ->first();
            }

            if (! $productoDb && ! empty($nombre)) {
                $productoDb = Producto::where('nombre', $nombre)
                    ->orWhere('nombre', 'LIKE', "%{$nombre}%")
                    ->first();
            }

            if ($productoDb) {
                if ($costoUnitario <= 0) {
                    $costoUnitario = (float) ($productoDb->costo_promedio ?? 0);
                }
                $tarifaIva = (float) $productoDb->tarifa_iva_porcentaje;
                $codigoIva = $productoDb->codigo_iva;
            } else {
                $tarifaIva = 15.0; // Por defecto general
                $codigoIva = '2';
                $unmappedCount++;
            }

            $lineSubtotal = round($cantidad * $costoUnitario, 2);
            $lineIva = ($tarifaIva > 0) ? round($lineSubtotal * ($tarifaIva / 100), 2) : 0.0;

            $subtotalSinImpuestos += $lineSubtotal;
            $totalIva += $lineIva;

            $detalles[] = [
                'codigo' => $codigo ?: ($productoDb ? $productoDb->codigo_principal : 'S/C'),
                'descripcion' => $nombre ?: ($productoDb ? $productoDb->nombre : 'Producto importado'),
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'subtotal' => $lineSubtotal,
                'tarifa_iva' => $tarifaIva,
                'codigo_iva' => $codigoIva,
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

        if (empty($detalles)) {
            throw new Exception('No se encontraron productos válidos para importar en el archivo.');
        }

        return [
            'success' => true,
            'total_items' => count($detalles),
            'unmapped_items' => $unmappedCount,
            'subtotal' => round($subtotalSinImpuestos, 2),
            'iva' => round($totalIva, 2),
            'total' => round($subtotalSinImpuestos + $totalIva, 2),
            'detalles' => $detalles,
        ];
    }

    /**
     * Procesa un archivo Excel/CSV para una Nota de Crédito de Compra.
     *
     * @throws Exception
     */
    public function parseDetallesNotaCredito(UploadedFile $file, ?int $compraId = null): array
    {
        $rows = $this->extractRowsFromFile($file);
        if (empty($rows)) {
            throw new Exception('El archivo cargado está vacío.');
        }

        $headerInfo = $this->detectHeaders($rows);
        $dataRows = array_slice($rows, $headerInfo['header_row_index'] + 1);

        $compra = null;
        if ($compraId) {
            $compra = Compra::with(['detalles.producto', 'notasCredito.detalles'])->find($compraId);
        }

        $itemsAfectados = [];

        foreach ($dataRows as $row) {
            $codigo = trim((string) ($row[$headerInfo['indices']['codigo']] ?? ''));
            $nombre = trim((string) ($row[$headerInfo['indices']['nombre']] ?? ''));
            $cantRaw = $row[$headerInfo['indices']['cantidad']] ?? '0';

            $cantidad = $this->limpiarNumero($cantRaw);

            if (empty($codigo) && empty($nombre)) {
                continue;
            }

            if ($cantidad <= 0) {
                continue;
            }

            // Buscar en ítems de la compra seleccionada si se especificó
            $compraDetalle = null;
            if ($compra) {
                $compraDetalle = $compra->detalles->first(function ($d) use ($codigo, $nombre) {
                    if (! $d->producto) {
                        return false;
                    }

                    return ($codigo && ($d->producto->codigo_principal === $codigo || $d->producto->codigo_auxiliar === $codigo))
                        || ($nombre && (strcasecmp($d->producto->nombre, $nombre) === 0 || stripos($d->producto->nombre, $nombre) !== false));
                });
            }

            // Si no se encuentra en compra, buscar en catálogo global
            $productoDb = $compraDetalle ? $compraDetalle->producto : null;
            if (! $productoDb) {
                if (! empty($codigo)) {
                    $productoDb = Producto::where('codigo_principal', $codigo)->orWhere('codigo_auxiliar', $codigo)->first();
                }
                if (! $productoDb && ! empty($nombre)) {
                    $productoDb = Producto::where('nombre', $nombre)->first();
                }
            }

            $itemsAfectados[] = [
                'codigo' => $codigo ?: ($productoDb ? $productoDb->codigo_principal : 'S/C'),
                'nombre' => $nombre ?: ($productoDb ? $productoDb->nombre : 'Producto'),
                'cantidad' => $cantidad,
                'producto_id' => $productoDb ? $productoDb->id : null,
                'costo_unitario' => $compraDetalle ? (float) $compraDetalle->costo_unitario : ($productoDb ? (float) $productoDb->costo_promedio : 0.0),
                'en_compra' => (bool) $compraDetalle,
            ];
        }

        return [
            'success' => true,
            'items' => $itemsAfectados,
        ];
    }

    /**
     * Extrae una matriz 2D de filas y columnas desde un archivo (.xlsx o .csv).
     *
     * @throws Exception
     */
    protected function extractRowsFromFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($ext === 'xlsx') {
            return $this->extractRowsFromXlsx($path);
        }

        // CSV / TXT / TSV
        return $this->extractRowsFromCsv($path);
    }

    /**
     * Lee un archivo XLSX usando ZipArchive y SimpleXML nativos.
     */
    protected function extractRowsFromXlsx(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new Exception('No se pudo abrir el archivo Excel (.xlsx).');
        }

        // 1. Cargar cadenas compartidas (sharedStrings.xml) si existen
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sxml = simplexml_load_string($sharedXml);
            if ($sxml && isset($sxml->si)) {
                foreach ($sxml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string) $val->t;
                    } elseif (isset($val->r)) {
                        $text = '';
                        foreach ($val->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Cargar hoja 1 (sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new Exception('No se encontró la hoja de cálculo en el archivo Excel.');
        }

        $xml = simplexml_load_string($sheetXml);
        $zip->close();

        if (! $xml || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $r) {
            $rowValues = [];
            $currentColIdx = 0;

            foreach ($r->c as $c) {
                // Determinar índice de columna a partir de referencia de celda (ej: A1 -> 0, C1 -> 2)
                $cellRef = (string) $c['r'];
                $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                $colIndex = $this->columnLetterToIndex($colLetters);

                // Rellenar columnas vacías intermedias
                while ($currentColIdx < $colIndex) {
                    $rowValues[$currentColIdx] = '';
                    $currentColIdx++;
                }

                $type = (string) $c['t'];
                $val = (string) $c->v;

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $rowValues[$colIndex] = $sharedStrings[(int) $val];
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $rowValues[$colIndex] = (string) $c->is->t;
                } else {
                    $rowValues[$colIndex] = $val;
                }

                $currentColIdx = $colIndex + 1;
            }

            if (! empty(array_filter($rowValues, fn ($v) => trim($v) !== ''))) {
                $rows[] = $rowValues;
            }
        }

        return $rows;
    }

    /**
     * Convierte letras de columna de Excel (A, B, AA) a índice numérico 0-indexed.
     */
    protected function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * Lee un archivo CSV detectando delimitadores y codificaciones comunes.
     */
    protected function extractRowsFromCsv(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception('No se pudo leer el archivo CSV.');
        }

        // Remover BOM si existe
        $content = preg_replace('/^[\xEF\xBB\xBF\xFE\xFF\xFF\xFE]+/', '', $content);

        // Detectar si no es UTF-8 y convertir
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252, auto');
        }

        // Detectar delimitador (coma, punto y coma, tabulador o pipe)
        $firstLines = substr($content, 0, 2048);
        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach ($delimiters as $delim => &$count) {
            $count = count(explode($delim, $firstLines));
        }
        arsort($delimiters);
        $chosenDelimiter = key($delimiters);

        $tempHandle = fopen('php://memory', 'r+');
        fwrite($tempHandle, $content);
        rewind($tempHandle);

        $rows = [];
        while (($data = fgetcsv($tempHandle, 4096, $chosenDelimiter)) !== false) {
            if (! empty(array_filter($data, fn ($v) => trim((string) $v) !== ''))) {
                $rows[] = array_map('trim', $data);
            }
        }

        fclose($tempHandle);

        return $rows;
    }

    /**
     * Identifica la fila de encabezados y asocia las columnas necesarias.
     */
    protected function detectHeaders(array $rows): array
    {
        $codigoSynonyms = ['codigo', 'codigo_principal', 'cod', 'sku', 'referencia', 'cod_producto', 'codigo_producto', 'item_code'];
        $cantidadSynonyms = ['cantidad', 'cant', 'qty', 'unidades', 'uds', 'cantidad_comprada', 'cantidad_devolver'];
        $costoSynonyms = ['costo', 'costo_unitario', 'precio', 'precio_unitario', 'p_unit', 'cost', 'precio_costo', 'valor_unitario'];
        $nombreSynonyms = ['nombre', 'descripcion', 'producto', 'nombre_producto', 'item', 'detalle', 'descripcion_producto'];

        $headerRowIdx = 0;
        $indices = [
            'codigo' => 0,
            'nombre' => 1,
            'cantidad' => 2,
            'costo' => 3,
        ];

        // Buscar entre las primeras 5 filas
        foreach (array_slice($rows, 0, 5, true) as $idx => $row) {
            $foundCodigo = null;
            $foundCant = null;
            $foundCosto = null;
            $foundNombre = null;

            foreach ($row as $colIdx => $cellVal) {
                $cleanVal = strtolower(trim((string) $cellVal));
                $cleanVal = str_replace(['.', '_', '-', ' '], '', $cleanVal);

                foreach ($codigoSynonyms as $s) {
                    if (str_replace(['.', '_', '-'], '', $s) === $cleanVal) {
                        $foundCodigo = $colIdx;
                        break;
                    }
                }
                foreach ($cantidadSynonyms as $s) {
                    if (str_replace(['.', '_', '-'], '', $s) === $cleanVal) {
                        $foundCant = $colIdx;
                        break;
                    }
                }
                foreach ($costoSynonyms as $s) {
                    if (str_replace(['.', '_', '-'], '', $s) === $cleanVal) {
                        $foundCosto = $colIdx;
                        break;
                    }
                }
                foreach ($nombreSynonyms as $s) {
                    if (str_replace(['.', '_', '-'], '', $s) === $cleanVal) {
                        $foundNombre = $colIdx;
                        break;
                    }
                }
            }

            // Si se encontraron al menos 2 columnas clave, es la fila de encabezados
            if ($foundCodigo !== null || $foundCant !== null || $foundCosto !== null) {
                $headerRowIdx = $idx;
                if ($foundCodigo !== null) {
                    $indices['codigo'] = $foundCodigo;
                }
                if ($foundNombre !== null) {
                    $indices['nombre'] = $foundNombre;
                }
                if ($foundCant !== null) {
                    $indices['cantidad'] = $foundCant;
                }
                if ($foundCosto !== null) {
                    $indices['costo'] = $foundCosto;
                }
                break;
            }
        }

        return [
            'header_row_index' => $headerRowIdx,
            'indices' => $indices,
        ];
    }

    /**
     * Limpia y convierte cadenas de texto numéricas con comas o puntos.
     */
    protected function limpiarNumero($val): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }

        $str = trim((string) $val);
        $str = preg_replace('/[^\d,\.\-]/', '', $str);

        // Si tiene formato 1.250,50 -> 1250.50
        if (strpos($str, ',') !== false && strpos($str, '.') !== false) {
            if (strrpos($str, ',') > strrpos($str, '.')) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        } elseif (strpos($str, ',') !== false) {
            $str = str_replace(',', '.', $str);
        }

        return (float) $str;
    }

    /**
     * Convierte índice numérico de columna (0-indexed) a letras de columna Excel (0 -> A, 27 -> AB).
     */
    protected function indexToColumnLetter(int $index): string
    {
        $letter = '';
        $index += 1;
        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $letter = chr(65 + $modulo).$letter;
            $index = intdiv($index - $modulo, 26);
        }

        return $letter;
    }

    /**
     * Genera el contenido binario de un archivo Excel (.xlsx) nativo sin dependencias externas.
     *
     * @param  array  $rows  Matriz 2D de filas y columnas
     * @param  string  $sheetName  Nombre de la pestaña
     * @return string Contenido binario del archivo .xlsx
     *
     * @throws Exception
     */
    public function buildXlsx(array $rows, string $sheetName = 'Hoja1'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('No se pudo inicializar el archivo temporal para generar el Excel.');
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $cleanSheetName = htmlspecialchars($sheetName, ENT_XML1, 'UTF-8');
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$cleanSheetName.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF0D9488"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        foreach ($rows as $rowIdx => $row) {
            $rNum = $rowIdx + 1;
            $sheetXml .= '<row r="'.$rNum.'">';
            foreach (array_values($row) as $colIdx => $val) {
                $cellRef = $this->indexToColumnLetter($colIdx).$rNum;
                $isHeader = ($rowIdx === 0);
                $styleAttr = $isHeader ? ' s="1"' : '';

                if (is_numeric($val) && ! $isHeader && ! (is_string($val) && str_starts_with($val, '0') && strlen($val) > 1)) {
                    $sheetXml .= '<c r="'.$cellRef.'"'.$styleAttr.'><v>'.$val.'</v></c>';
                } else {
                    $escaped = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr"'.$styleAttr.'><is><t>'.$escaped.'</t></is></c>';
                }
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        $binary = file_get_contents($tempFile);
        @unlink($tempFile);

        return $binary;
    }

    /**
     * Genera y descarga plantilla de ejemplo para compras en formato Excel (.xlsx).
     */
    public function generarPlantillaCompra(): StreamedResponse
    {
        $filename = 'plantilla_detalle_compra_'.date('Ymd').'.xlsx';
        $rows = [
            ['codigo_producto', 'nombre_producto', 'cantidad', 'costo_unitario'],
        ];

        // Muestras de ejemplo con productos reales si existen
        $ejemplos = Producto::take(3)->get();
        if ($ejemplos->isNotEmpty()) {
            foreach ($ejemplos as $p) {
                $rows[] = [
                    $p->codigo_principal ?: 'PROD001',
                    $p->nombre,
                    10,
                    round((float) ($p->costo_promedio ?: $p->precio_unitario * 0.7), 2),
                ];
            }
        } else {
            $rows[] = ['PROD-001', 'Vitamina C 1000mg x 60 Tabletas', 15, 4.50];
            $rows[] = ['PROD-002', 'Miel de Abejas Pura 500ml', 20, 3.20];
            $rows[] = ['PROD-003', 'Colágeno Hidrolizado 300g', 8, 12.00];
        }

        $content = $this->buildXlsx($rows, 'Detalle Compra');

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($content),
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($content) {
            echo $content;
        }, 200, $headers);
    }

    /**
     * Genera y descarga plantilla de ejemplo para notas de crédito en formato Excel (.xlsx).
     */
    public function generarPlantillaNotaCredito(): StreamedResponse
    {
        $filename = 'plantilla_detalle_nota_credito_'.date('Ymd').'.xlsx';
        $rows = [
            ['codigo_producto', 'nombre_producto', 'cantidad_devolver'],
            ['PROD-001', 'Vitamina C 1000mg x 60 Tabletas', 2],
            ['PROD-002', 'Miel de Abejas Pura 500ml', 5],
        ];

        $content = $this->buildXlsx($rows, 'Devoluciones NC');

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($content),
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($content) {
            echo $content;
        }, 200, $headers);
    }

    /**
     * Procesa un archivo Excel/CSV para el catálogo maestro de productos.
     *
     * @throws Exception
     */
    public function parseProductosCatalogo(UploadedFile $file): array
    {
        $rows = $this->extractRowsFromFile($file);
        if (empty($rows)) {
            throw new Exception('El archivo de productos está vacío.');
        }

        $headerInfo = $this->detectProductoHeaders($rows);
        $dataRows = array_slice($rows, $headerInfo['header_row_index'] + 1);

        $productos = [];
        foreach ($dataRows as $row) {
            $codigo = trim((string) ($row[$headerInfo['indices']['codigo_principal']] ?? ''));
            $codigoAux = isset($headerInfo['indices']['codigo_auxiliar']) ? trim((string) ($row[$headerInfo['indices']['codigo_auxiliar']] ?? '')) : '';
            $nombre = trim((string) ($row[$headerInfo['indices']['nombre']] ?? ''));
            $categoria = isset($headerInfo['indices']['categoria']) ? trim((string) ($row[$headerInfo['indices']['categoria']] ?? '')) : '';
            $tipoRaw = isset($headerInfo['indices']['tipo_producto']) ? trim((string) ($row[$headerInfo['indices']['tipo_producto']] ?? '')) : 'BIEN';
            $costoRaw = isset($headerInfo['indices']['costo_promedio']) ? ($row[$headerInfo['indices']['costo_promedio']] ?? '0') : '0';
            $precioRaw = isset($headerInfo['indices']['precio_unitario']) ? ($row[$headerInfo['indices']['precio_unitario']] ?? '0') : '0';
            $ivaRaw = isset($headerInfo['indices']['tarifa_iva']) ? ($row[$headerInfo['indices']['tarifa_iva']] ?? '15') : '15';
            $stockRaw = isset($headerInfo['indices']['stock_inicial']) ? ($row[$headerInfo['indices']['stock_inicial']] ?? '0') : '0';
            $minimoRaw = isset($headerInfo['indices']['stock_minimo']) ? ($row[$headerInfo['indices']['stock_minimo']] ?? '5') : '5';

            if (empty($codigo) && empty($nombre)) {
                continue;
            }

            $costo = $this->limpiarNumero($costoRaw);
            $precio = $this->limpiarNumero($precioRaw);
            $stockInicial = $this->limpiarNumero($stockRaw);
            $stockMinimo = $this->limpiarNumero($minimoRaw);
            if ($stockMinimo <= 0) {
                $stockMinimo = 5.0;
            }

            // Determinar IVA
            $ivaStr = str_replace('%', '', trim((string) $ivaRaw));
            if ($ivaStr === '0' || $ivaStr === '0.00' || $ivaStr === '0%') {
                $codigoIva = '0';
                $tarifaIva = 0.0;
            } elseif ($ivaStr === '5' || $ivaStr === '4') {
                $codigoIva = '4';
                $tarifaIva = 5.0;
            } else {
                $codigoIva = '2';
                $tarifaIva = 15.0;
            }

            $tipoProducto = (stripos($tipoRaw, 'serv') !== false) ? 'SERVICIO' : 'BIEN';

            $productos[] = [
                'codigo_principal' => $codigo ?: 'PROD-'.rand(10000, 99999),
                'codigo_auxiliar' => $codigoAux ?: null,
                'nombre' => $nombre ?: ($codigo ?: 'Producto sin nombre'),
                'categoria' => $categoria ?: 'General',
                'tipo_producto' => $tipoProducto,
                'costo_promedio' => max(0.0, $costo),
                'precio_unitario' => $precio > 0 ? $precio : round(max(0.01, $costo * 1.30), 2),
                'codigo_iva' => $codigoIva,
                'tarifa_iva' => $tarifaIva,
                'stock_inicial' => max(0.0, $stockInicial),
                'stock_minimo' => $stockMinimo,
            ];
        }

        if (empty($productos)) {
            throw new Exception('No se encontraron registros válidos de productos en el archivo.');
        }

        return $productos;
    }

    /**
     * Detecta encabezados para catálogo de productos.
     */
    protected function detectProductoHeaders(array $rows): array
    {
        $indices = [
            'codigo_principal' => 0,
            'codigo_auxiliar' => null,
            'nombre' => 1,
            'categoria' => null,
            'tipo_producto' => null,
            'costo_promedio' => null,
            'precio_unitario' => null,
            'tarifa_iva' => null,
            'stock_inicial' => null,
            'stock_minimo' => null,
        ];

        $headerRowIdx = 0;

        foreach (array_slice($rows, 0, 5, true) as $idx => $row) {
            $foundAny = false;
            foreach ($row as $colIdx => $cellVal) {
                $clean = strtolower(trim((string) $cellVal));
                $clean = str_replace(['.', '_', '-', ' '], '', $clean);

                if (in_array($clean, ['codigoprincipal', 'codigo', 'cod', 'sku', 'itemcode', 'codigoproducto'])) {
                    $indices['codigo_principal'] = $colIdx;
                    $foundAny = true;
                } elseif (in_array($clean, ['codigoauxiliar', 'codigoaux', 'codaux', 'auxiliar', 'barra', 'codigobarras'])) {
                    $indices['codigo_auxiliar'] = $colIdx;
                } elseif (in_array($clean, ['nombre', 'descripcion', 'producto', 'nombreproducto', 'descripcionproducto', 'item'])) {
                    $indices['nombre'] = $colIdx;
                    $foundAny = true;
                } elseif (in_array($clean, ['categoria', 'categorianombre', 'cat', 'rubro', 'linea', 'familia'])) {
                    $indices['categoria'] = $colIdx;
                } elseif (in_array($clean, ['tipoproducto', 'tipo', 'tipoitem', 'naturaleza'])) {
                    $indices['tipo_producto'] = $colIdx;
                } elseif (in_array($clean, ['costopromedio', 'costo', 'costounitario', 'preciocosto', 'cost'])) {
                    $indices['costo_promedio'] = $colIdx;
                } elseif (in_array($clean, ['preciounitario', 'precio', 'pvp', 'precioventa', 'valorunitario', 'punit'])) {
                    $indices['precio_unitario'] = $colIdx;
                } elseif (in_array($clean, ['tarifaiva', 'iva', 'porcentajeiva', 'codigoiva', 'impuesto'])) {
                    $indices['tarifa_iva'] = $colIdx;
                } elseif (in_array($clean, ['stockinicial', 'stock', 'cantidad', 'existenciainicial', 'stockactual'])) {
                    $indices['stock_inicial'] = $colIdx;
                } elseif (in_array($clean, ['stockminimo', 'minimo', 'alertaminima', 'stockmin'])) {
                    $indices['stock_minimo'] = $colIdx;
                }
            }

            if ($foundAny) {
                $headerRowIdx = $idx;
                break;
            }
        }

        return [
            'header_row_index' => $headerRowIdx,
            'indices' => $indices,
        ];
    }

    /**
     * Genera y descarga plantilla de catálogo de productos en formato Excel (.xlsx).
     */
    public function generarPlantillaProductos(): StreamedResponse
    {
        $filename = 'plantilla_catalogo_productos_'.date('Ymd').'.xlsx';
        $rows = [
            [
                'codigo_principal',
                'codigo_auxiliar',
                'nombre_producto',
                'categoria',
                'tipo_producto',
                'costo_promedio',
                'precio_unitario',
                'tarifa_iva',
                'stock_inicial',
                'stock_minimo',
            ],
            ['PROD-001', '7861234567890', 'Vitamina C 1000mg x 60 Tabletas', 'Suplementos', 'BIEN', 4.50, 7.50, 15, 25, 5],
            ['PROD-002', '7861234567891', 'Miel de Abejas Natural 500ml', 'Naturales', 'BIEN', 3.20, 5.00, 0, 40, 10],
            ['PROD-003', '7861234567892', 'Colágeno Hidrolizado 300g Polvo', 'Suplementos', 'BIEN', 12.00, 19.99, 15, 15, 3],
            ['SERV-001', '', 'Consulta Nutricional y Asesoría', 'Servicios', 'SERVICIO', 0.00, 20.00, 0, 0, 0],
        ];

        $content = $this->buildXlsx($rows, 'Catálogo Productos');

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($content),
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($content) {
            echo $content;
        }, 200, $headers);
    }
}
