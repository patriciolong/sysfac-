<?php

namespace Tests\Feature;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ExcelImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CompraImportXmlExcelTest extends TestCase
{
    use DatabaseTransactions;

    private function getTestUser(): User
    {
        return User::first() ?? User::factory()->create();
    }

    public function test_parse_factura_xml_extracts_provider_and_items(): void
    {
        $user = $this->getTestUser();

        // Create sample product to test matching
        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'General', 'estado' => 'ACTIVO']);
        $prod = Producto::create([
            'codigo_principal' => 'XMLPROD01',
            'nombre' => 'Suplemento Zinc 50mg',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'precio_unitario' => 12.00,
            'costo_promedio' => 6.50,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<factura id="comprobante" version="1.1.0">
    <infoTributaria>
        <ambiente>1</ambiente>
        <tipoEmision>1</tipoEmision>
        <razonSocial>DISTRIBUIDORA FARMACEUTICA ECUADOR CIA. LTDA.</razonSocial>
        <nombreComercial>DISFARMA ECUADOR</nombreComercial>
        <ruc>1790012345001</ruc>
        <claveAcceso>1809202601179001234500110010020000123451234567819</claveAcceso>
        <codDoc>01</codDoc>
        <estab>001</estab>
        <ptoEmi>002</ptoEmi>
        <secuencial>000012345</secuencial>
        <dirMatriz>Av. de los Shyris y Naciones Unidas</dirMatriz>
    </infoTributaria>
    <infoFactura>
        <fechaEmision>18/09/2026</fechaEmision>
        <dirEstablecimiento>Av. de los Shyris y Naciones Unidas</dirEstablecimiento>
        <totalSinImpuestos>130.00</totalSinImpuestos>
        <totalDescuento>0.00</totalDescuento>
        <totalConImpuestos>
            <totalImpuesto>
                <codigo>2</codigo>
                <codigoPorcentaje>4</codigoPorcentaje>
                <baseImponible>130.00</baseImponible>
                <tarifa>15.00</tarifa>
                <valor>19.50</valor>
            </totalImpuesto>
        </totalConImpuestos>
        <importeTotal>149.50</importeTotal>
        <moneda>DOLAR</moneda>
    </infoFactura>
    <detalles>
        <detalle>
            <codigoPrincipal>XMLPROD01</codigoPrincipal>
            <descripcion>Suplemento Zinc 50mg</descripcion>
            <cantidad>20.00</cantidad>
            <precioUnitario>6.50</precioUnitario>
            <descuento>0.00</descuento>
            <precioTotalSinImpuesto>130.00</precioTotalSinImpuesto>
            <impuestos>
                <impuesto>
                    <codigo>2</codigo>
                    <codigoPorcentaje>4</codigoPorcentaje>
                    <tarifa>15.00</tarifa>
                    <baseImponible>130.00</baseImponible>
                    <valor>19.50</valor>
                </impuesto>
            </impuestos>
        </detalle>
    </detalles>
    <infoAdicional>
        <campoAdicional nombre="Email">facturas@disfarma.com</campoAdicional>
        <campoAdicional nombre="Telefono">022998877</campoAdicional>
    </infoAdicional>
</factura>
XML;

        $file = UploadedFile::fake()->createWithContent('factura_proveedor.xml', $xmlContent);

        $response = $this->actingAs($user)->postJson(route('compras.parseXml'), [
            'xml_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tipo_comprobante' => 'FACTURA',
                    'proveedor' => [
                        'identificacion' => '1790012345001',
                        'razon_social' => 'DISTRIBUIDORA FARMACEUTICA ECUADOR CIA. LTDA.',
                    ],
                    'factura' => [
                        'numero_factura' => '001-002-000012345',
                        'fecha_emision' => '2026-09-18',
                        'total' => 149.50,
                    ],
                ],
            ]);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData['detalles']);
        $this->assertEquals('XMLPROD01', $responseData['detalles'][0]['codigo']);
        $this->assertEquals(20.0, $responseData['detalles'][0]['cantidad']);
        $this->assertEquals(6.50, $responseData['detalles'][0]['costo_unitario']);
        $this->assertNotNull($responseData['detalles'][0]['matched_producto']);
        $this->assertEquals($prod->id, $responseData['detalles'][0]['matched_producto']['id']);
    }

    public function test_parse_nota_credito_xml_extracts_data(): void
    {
        $user = $this->getTestUser();
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Central', 'codigo' => 'B01', 'estado' => 'ACTIVO']);
        $proveedor = Proveedor::create([
            'razon_social' => 'LABORATORIOS BIOPHARMA CIA. LTDA.',
            'identificacion' => '1799887766001',
            'tipo_identificacion' => '04',
            'correo' => 'ventas@biopharma.com',
        ]);

        // Create purchase to match
        $compra = Compra::create([
            'proveedor_id' => $proveedor->id,
            'bodega_id' => $bodega->id,
            'usuario_id' => $user->id,
            'numero_factura' => '001-001-000004567',
            'fecha_emision' => '2026-09-10',
            'subtotal_sin_impuestos' => 200.00,
            'iva' => 30.00,
            'total' => 230.00,
        ]);

        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<notaCredito id="comprobante" version="1.0.0">
    <infoTributaria>
        <ambiente>1</ambiente>
        <tipoEmision>1</tipoEmision>
        <razonSocial>LABORATORIOS BIOPHARMA CIA. LTDA.</razonSocial>
        <ruc>1799887766001</ruc>
        <claveAcceso>1809202604179988776600110010010000000451234567814</claveAcceso>
        <codDoc>04</codDoc>
        <estab>001</estab>
        <ptoEmi>001</ptoEmi>
        <secuencial>000000045</secuencial>
        <dirMatriz>Av. Amazonas 123</dirMatriz>
    </infoTributaria>
    <infoNotaCredito>
        <fechaEmision>18/09/2026</fechaEmision>
        <numDocModificado>001-001-000004567</numDocModificado>
        <fechaEmisionDocSustento>10/09/2026</fechaEmisionDocSustento>
        <totalSinImpuestos>50.00</totalSinImpuestos>
        <motivo>Devolución de mercadería dañada en transporte</motivo>
        <valorModificacion>57.50</valorModificacion>
    </infoNotaCredito>
    <detalles>
        <detalle>
            <codigoInterno>PROD_DEV_01</codigoInterno>
            <descripcion>Producto Deteriorado A</descripcion>
            <cantidad>5.00</cantidad>
            <precioUnitario>10.00</precioUnitario>
            <descuento>0.00</descuento>
            <precioTotalSinImpuesto>50.00</precioTotalSinImpuesto>
            <impuestos>
                <impuesto>
                    <codigo>2</codigo>
                    <codigoPorcentaje>4</codigoPorcentaje>
                    <tarifa>15.00</tarifa>
                    <baseImponible>50.00</baseImponible>
                    <valor>7.50</valor>
                </impuesto>
            </impuestos>
        </detalle>
    </detalles>
</notaCredito>
XML;

        $file = UploadedFile::fake()->createWithContent('nc_proveedor.xml', $xmlContent);

        $response = $this->actingAs($user)->postJson(route('compras.notas-credito.parseXml'), [
            'xml_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tipo_comprobante' => 'NOTA_CREDITO',
                    'nota_credito' => [
                        'numero_nota_credito' => '001-001-000000045',
                        'num_doc_modificado' => '001-001-000004567',
                        'motivo' => 'Devolución de mercadería dañada en transporte',
                        'total' => 57.50,
                    ],
                    'compra_modificada' => [
                        'found' => true,
                        'id' => $compra->id,
                    ],
                ],
            ]);
    }

    public function test_parse_excel_compra_xlsx(): void
    {
        $user = $this->getTestUser();
        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'General', 'estado' => 'ACTIVO']);

        $prod = Producto::create([
            'codigo_principal' => 'XLSXPROD10',
            'nombre' => 'Aceite de Coco Organico 500ml',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'precio_unitario' => 8.50,
            'costo_promedio' => 4.25,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        $excelService = new ExcelImportService;
        $xlsxContent = $excelService->buildXlsx([
            ['codigo_producto', 'nombre_producto', 'cantidad', 'costo_unitario'],
            ['XLSXPROD10', 'Aceite de Coco Organico 500ml', 15, 4.25],
        ]);

        $file = UploadedFile::fake()->createWithContent('items_compra.xlsx', $xlsxContent);

        $response = $this->actingAs($user)->postJson(route('compras.parseExcel'), [
            'excel_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_items' => 1,
                    'subtotal' => 63.75, // 15 * 4.25
                ],
            ]);
    }

    public function test_descargar_plantillas_excel_xlsx(): void
    {
        $user = $this->getTestUser();

        $resCompras = $this->actingAs($user)->get(route('compras.plantillaExcel'));
        $resCompras->assertStatus(200);
        $resCompras->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resCompras->headers->get('content-disposition'));

        $resNC = $this->actingAs($user)->get(route('compras.notas-credito.plantillaExcel'));
        $resNC->assertStatus(200);
        $resNC->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resNC->headers->get('content-disposition'));

        $resProd = $this->actingAs($user)->get(route('productos.plantillaExcel'));
        $resProd->assertStatus(200);
        $resProd->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resProd->headers->get('content-disposition'));
    }

    public function test_import_productos_catalogo_xlsx(): void
    {
        $user = $this->getTestUser();
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Principal', 'codigo' => 'B01', 'estado' => 'ACTIVO']);

        $excelService = new ExcelImportService;
        $xlsxContent = $excelService->buildXlsx([
            ['codigo_principal', 'codigo_auxiliar', 'nombre_producto', 'categoria', 'tipo_producto', 'costo_promedio', 'precio_unitario', 'tarifa_iva', 'stock_inicial', 'stock_minimo'],
            ['PROD-IMPORT-01', '786000000001', 'Colágeno Marino Premium 500g', 'Nutrición Especial', 'BIEN', 14.50, 24.99, '15', 30, 5],
            ['SERV-IMPORT-01', '', 'Consulta Especializada Nutrición', 'Servicios Profesionales', 'SERVICIO', 0.00, 35.00, '0', 0, 0],
        ]);

        $file = UploadedFile::fake()->createWithContent('catalogo_import.xlsx', $xlsxContent);

        $response = $this->actingAs($user)->postJson(route('productos.importExcel'), [
            'excel_file' => $file,
            'bodega_id' => $bodega->id,
            'actualizar_existentes' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
                'creados' => 2,
            ]);

        // Assert products were created
        $this->assertDatabaseHas('inventario_productos', [
            'codigo_principal' => 'PROD-IMPORT-01',
            'nombre' => 'Colágeno Marino Premium 500g',
            'tipo_producto' => 'BIEN',
            'precio_unitario' => 24.99,
        ]);

        $this->assertDatabaseHas('inventario_productos', [
            'codigo_principal' => 'SERV-IMPORT-01',
            'nombre' => 'Consulta Especializada Nutrición',
            'tipo_producto' => 'SERVICIO',
            'precio_unitario' => 35.00,
        ]);

        // Assert categories were created automatically
        $this->assertDatabaseHas('inventario_categorias', [
            'nombre' => 'Nutrición Especial',
        ]);
        $this->assertDatabaseHas('inventario_categorias', [
            'nombre' => 'Servicios Profesionales',
        ]);
    }

    public function test_import_exact_plantilla_output_xlsx(): void
    {
        $user = $this->getTestUser();
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Principal', 'codigo' => 'B01', 'estado' => 'ACTIVO']);

        $excelService = new ExcelImportService;
        $responseStream = $excelService->generarPlantillaProductos();
        ob_start();
        $responseStream->sendContent();
        $content = ob_get_clean();

        $file = UploadedFile::fake()->createWithContent('plantilla_catalogo_productos_20260918.xlsx', $content);

        $response = $this->actingAs($user)->postJson(route('productos.importExcel'), [
            'excel_file' => $file,
            'bodega_id' => $bodega->id,
            'actualizar_existentes' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 4,
            ]);
    }
}
