<?php

namespace Tests\Feature;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\CompraNotaCredito;
use App\Models\InventarioGeneral;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BodegaTest extends TestCase
{
    use DatabaseTransactions;

    private function getAuthUser(): User
    {
        return User::first() ?? User::factory()->create();
    }

    public function test_usuario_autenticado_puede_ver_listado_de_bodegas(): void
    {
        $user = $this->getAuthUser();

        $response = $this->actingAs($user)->get('/bodegas');

        $response->assertStatus(200);
        $response->assertSee('Gestión de Bodegas y Almacenes');
        $response->assertSee('Nueva Bodega');
    }

    public function test_creacion_de_nueva_bodega_con_sincronizacion_de_catalogo(): void
    {
        $user = $this->getAuthUser();

        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Test Cat', 'estado' => 'ACTIVO']);
        $prod = Producto::create([
            'codigo_principal' => 'TEST-BOD-'.rand(1000, 9999),
            'nombre' => 'Producto Prueba Bodega',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 12.50,
            'precio_unitario' => 20.00,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        $randomCode = 'BOD-TEST-'.rand(1000, 9999);
        $randomName = 'Bodega Sucursal Norte '.rand(100, 999);

        $response = $this->actingAs($user)->post('/bodegas', [
            'codigo' => $randomCode,
            'nombre' => $randomName,
            'ubicacion' => 'Av. Amazonas y Naciones Unidas',
            'descripcion' => 'Almacén secundario para repuestos',
            'responsable' => 'Ing. Juan Perez',
            'telefono' => '0998765432',
            'estado' => 'ACTIVO',
            'es_principal' => '0',
        ]);

        $response->assertRedirect(route('bodegas.index'));

        $this->assertDatabaseHas('inventario_bodegas', [
            'codigo' => $randomCode,
            'nombre' => $randomName,
            'responsable' => 'Ing. Juan Perez',
        ]);

        $bodega = Bodega::where('codigo', $randomCode)->first();
        $this->assertNotNull($bodega);

        // Check auto-sync of catalog product into InventarioGeneral
        $this->assertDatabaseHas('inventario_general', [
            'bodega_id' => $bodega->id,
            'producto_id' => $prod->id,
            'stock_actual' => 0.0,
        ]);
    }

    public function test_actualizacion_y_cambio_de_bodega_principal(): void
    {
        $user = $this->getAuthUser();

        $bodegaA = Bodega::create([
            'codigo' => 'BOD-A-'.rand(1000, 9999),
            'nombre' => 'Bodega Alpha '.rand(100, 999),
            'estado' => 'ACTIVO',
            'es_principal' => true,
        ]);

        $bodegaB = Bodega::create([
            'codigo' => 'BOD-B-'.rand(1000, 9999),
            'nombre' => 'Bodega Beta '.rand(100, 999),
            'estado' => 'ACTIVO',
            'es_principal' => false,
        ]);

        // Make Bodega B the primary warehouse
        $response = $this->actingAs($user)->put("/bodegas/{$bodegaB->id}", [
            'codigo' => $bodegaB->codigo,
            'nombre' => $bodegaB->nombre.' Modificada',
            'ubicacion' => 'Sector Parque Industrial',
            'estado' => 'ACTIVO',
            'es_principal' => '1',
        ]);

        $response->assertRedirect(route('bodegas.index'));

        $this->assertTrue((bool) $bodegaB->fresh()->es_principal);
        $this->assertFalse((bool) $bodegaA->fresh()->es_principal);
    }

    public function test_seguridad_no_permite_eliminar_bodega_principal_o_con_stock(): void
    {
        $user = $this->getAuthUser();

        $bodegaPrincipal = Bodega::firstOrCreate(
            ['es_principal' => true],
            ['nombre' => 'Bodega Central Principal', 'codigo' => 'BOD-PRINCIPAL', 'estado' => 'ACTIVO']
        );

        // Attempting to delete principal warehouse should fail
        $response = $this->actingAs($user)->delete("/bodegas/{$bodegaPrincipal->id}");
        $response->assertRedirect(route('bodegas.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('inventario_bodegas', ['id' => $bodegaPrincipal->id]);

        // Non-principal warehouse with stock should also fail
        $bodegaStock = Bodega::create([
            'codigo' => 'BOD-STK-'.rand(1000, 9999),
            'nombre' => 'Bodega Con Stock '.rand(100, 999),
            'estado' => 'ACTIVO',
            'es_principal' => false,
        ]);

        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Cat General', 'estado' => 'ACTIVO']);
        $prod = Producto::create([
            'codigo_principal' => 'PROD-STK-'.rand(1000, 9999),
            'nombre' => 'Item con Stock',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 10,
            'precio_unitario' => 15,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        InventarioGeneral::updateOrCreate(
            ['bodega_id' => $bodegaStock->id, 'producto_id' => $prod->id],
            ['stock_actual' => 50.0, 'stock_minimo' => 5.0]
        );

        $response2 = $this->actingAs($user)->delete("/bodegas/{$bodegaStock->id}");
        $response2->assertRedirect(route('bodegas.index'));
        $response2->assertSessionHas('error');

        $this->assertDatabaseHas('inventario_bodegas', ['id' => $bodegaStock->id]);
    }

    public function test_exportacion_csv_de_bodegas(): void
    {
        $user = $this->getAuthUser();

        $response = $this->actingAs($user)->get('/bodegas/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_persistencia_y_descarga_de_xml_en_compras(): void
    {
        Storage::fake('public');
        $user = $this->getAuthUser();

        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Test', 'codigo' => 'B01', 'estado' => 'ACTIVO']);
        $proveedor = Proveedor::first() ?? Proveedor::create([
            'razon_social' => 'DISTRIBUIDORA FARMACEUTICA ECUADOR CIA. LTDA.',
            'identificacion' => '1790012345001',
            'tipo_identificacion' => '04',
            'estado' => 'ACTIVO',
        ]);

        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Cat XML', 'estado' => 'ACTIVO']);
        $prod = Producto::create([
            'codigo_principal' => 'XMLPROD01',
            'nombre' => 'Producto XML',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 10,
            'precio_unitario' => 15,
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
</factura>
XML;
        $fakeXml = UploadedFile::fake()->createWithContent('factura_001_001.xml', $xmlContent);

        // Upload XML via parseXml
        $parseResponse = $this->actingAs($user)->postJson('/compras/parse-xml', [
            'xml_file' => $fakeXml,
        ]);

        $parseResponse->assertStatus(200);
        $parseData = $parseResponse->json();
        $this->assertTrue($parseData['success']);
        $this->assertNotEmpty($parseData['xml_path']);
        $this->assertEquals('factura_001_001.xml', $parseData['xml_nombre_original']);

        // Register purchase with XML path
        $storeResponse = $this->actingAs($user)->post('/compras', [
            'proveedor_id' => $proveedor->id,
            'numero_factura' => '001-002-'.rand(1000000, 9999999),
            'fecha_emision' => date('Y-m-d'),
            'bodega_id' => $bodega->id,
            'xml_path' => $parseData['xml_path'],
            'xml_nombre_original' => $parseData['xml_nombre_original'],
            'detalles' => [
                [
                    'producto_id' => $prod->id,
                    'cantidad' => 10,
                    'costo_unitario' => 10,
                ],
            ],
        ]);

        $storeResponse->assertRedirect(route('compras.index'));

        $compra = Compra::where('xml_path', $parseData['xml_path'])->first();
        $this->assertNotNull($compra);
        $this->assertEquals('factura_001_001.xml', $compra->xml_nombre_original);

        // Test XML download route
        $downloadResponse = $this->actingAs($user)->get("/compras/{$compra->id}/descargar-xml");
        $downloadResponse->assertStatus(200);
        $this->assertTrue($downloadResponse->headers->has('content-disposition'));
    }

    public function test_persistencia_y_descarga_de_xml_en_notas_credito(): void
    {
        Storage::fake('public');
        $user = $this->getAuthUser();

        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Test', 'codigo' => 'B01', 'estado' => 'ACTIVO']);
        $proveedor = Proveedor::first() ?? Proveedor::create([
            'razon_social' => 'LABORATORIOS BIOPHARMA CIA. LTDA.',
            'identificacion' => '1799887766001',
            'tipo_identificacion' => '04',
            'estado' => 'ACTIVO',
        ]);

        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Cat NC XML', 'estado' => 'ACTIVO']);
        $prod = Producto::create([
            'codigo_principal' => 'PROD_DEV_01',
            'nombre' => 'Producto NC XML',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 20,
            'precio_unitario' => 30,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        $compra = Compra::create([
            'proveedor_id' => $proveedor->id,
            'bodega_id' => $bodega->id,
            'usuario_id' => 1,
            'numero_factura' => '001-001-000004567',
            'fecha_emision' => date('Y-m-d'),
            'subtotal_sin_impuestos' => 200,
            'iva' => 30,
            'total' => 230,
        ]);

        CompraDetalle::create([
            'compra_id' => $compra->id,
            'producto_id' => $prod->id,
            'cantidad' => 10,
            'costo_unitario' => 20,
            'costo_total' => 200,
        ]);

        InventarioGeneral::create([
            'bodega_id' => $bodega->id,
            'producto_id' => $prod->id,
            'stock_actual' => 10,
            'stock_minimo' => 5,
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
        $fakeXml = UploadedFile::fake()->createWithContent('nc_001_001.xml', $xmlContent);

        // Upload XML via parseXml
        $parseResponse = $this->actingAs($user)->postJson('/compras/notas-credito/parse-xml', [
            'xml_file' => $fakeXml,
        ]);

        $parseResponse->assertStatus(200);
        $parseData = $parseResponse->json();
        $this->assertTrue($parseData['success']);
        $this->assertNotEmpty($parseData['xml_path']);
        $this->assertEquals('nc_001_001.xml', $parseData['xml_nombre_original']);

        // Register NC with XML path
        $storeResponse = $this->actingAs($user)->post('/compras/notas-credito', [
            'compra_id' => $compra->id,
            'numero_nota_credito' => '001-001-'.rand(1000000, 9999999),
            'fecha_emision' => date('Y-m-d'),
            'motivo' => 'Devolución parcial mercadería defectuosa',
            'tipo_modificacion' => 'DEVOLUCION_MERCADERIA',
            'xml_path' => $parseData['xml_path'],
            'xml_nombre_original' => $parseData['xml_nombre_original'],
            'detalles' => [
                [
                    'producto_id' => $prod->id,
                    'cantidad' => 2,
                    'costo_unitario' => 20,
                ],
            ],
        ]);

        $storeResponse->assertRedirect(route('compras.notas-credito.index'));

        $nc = CompraNotaCredito::where('xml_path', $parseData['xml_path'])->first();
        $this->assertNotNull($nc);
        $this->assertEquals('nc_001_001.xml', $nc->xml_nombre_original);

        // Test XML download route
        $downloadResponse = $this->actingAs($user)->get("/compras/notas-credito/{$nc->id}/descargar-xml");
        $downloadResponse->assertStatus(200);
        $this->assertTrue($downloadResponse->headers->has('content-disposition'));
    }
}
