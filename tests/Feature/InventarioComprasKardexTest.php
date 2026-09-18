<?php

namespace Tests\Feature;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventarioComprasKardexTest extends TestCase
{
    use DatabaseTransactions;

    public function test_producto_creation_and_margin_calculation(): void
    {
        $user = User::first();
        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Test Cat', 'estado' => 'ACTIVO']);
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Central', 'codigo' => 'B01', 'estado' => 'ACTIVO']);

        $codigo = 'PROD_TEST_'.rand(10000, 99999);
        $productoData = [
            'codigo_principal' => $codigo,
            'nombre' => 'Producto de Prueba Automatizada',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 10.00,
            'precio_unitario' => 15.00,
            'codigo_iva' => '2',
            'stock_minimo' => 5,
            'stock_inicial' => 20,
            'bodega_id' => $bodega->id,
        ];

        $response = $this->actingAs($user)->post(route('productos.store'), $productoData);
        $response->assertRedirect(route('productos.index'));

        $producto = Producto::where('codigo_principal', $codigo)->first();
        $this->assertNotNull($producto);
        $this->assertEquals(10.00, (float) $producto->costo_promedio);
        $this->assertEquals(15.00, (float) $producto->precio_unitario);
        $this->assertEquals(5.00, (float) $producto->margen_ganancia);
        $this->assertEquals(50.00, (float) $producto->margen_porcentaje);

        // Verify stock in inventario_general
        $inv = InventarioGeneral::where('producto_id', $producto->id)->where('bodega_id', $bodega->id)->first();
        $this->assertNotNull($inv);
        $this->assertEquals(20, (float) $inv->stock_actual);

        // Verify Kardex initial movement
        $movDetalle = MovimientoDetalle::where('producto_id', $producto->id)->first();
        $this->assertNotNull($movDetalle);
        $this->assertEquals(20, (float) $movDetalle->cantidad);

        // Test Show JSON endpoint
        $showRes = $this->actingAs($user)->getJson(route('productos.show', $producto->id));
        $showRes->assertOk();
        $showRes->assertJsonPath('producto.codigo_principal', $codigo);

        // Test Toggle Estado endpoint (redirects back with status)
        $toggleRes = $this->actingAs($user)->post(route('productos.toggleEstado', $producto->id));
        $toggleRes->assertRedirect(route('productos.index'));
        $producto->refresh();
        $this->assertEquals('INACTIVO', $producto->estado);
    }

    public function test_compra_flow_updates_stock_cost_and_kardex_and_anular(): void
    {
        $user = User::first();
        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Test Cat', 'estado' => 'ACTIVO']);
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Central', 'codigo' => 'B01', 'estado' => 'ACTIVO']);
        $proveedor = Proveedor::first() ?? Proveedor::create([
            'razon_social' => 'Proveedor Test S.A.',
            'identificacion' => '1799999999001',
            'tipo_identificacion' => '04',
            'estado' => 'ACTIVO',
        ]);

        $codigo = 'COMP_TEST_'.rand(10000, 99999);
        // Create product with existing stock of 10 @ $10.00 each
        $producto = Producto::create([
            'codigo_principal' => $codigo,
            'nombre' => 'Producto Para Compra Test',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 10.00,
            'precio_unitario' => 20.00,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        InventarioGeneral::create([
            'producto_id' => $producto->id,
            'bodega_id' => $bodega->id,
            'stock_actual' => 10,
            'stock_minimo' => 2,
        ]);

        $numFactura = '001-001-'.rand(100000000, 999999999);
        // Purchase 10 units at $20.00 each
        // Weighted Average Cost should become: (10*10 + 10*20) / (10+10) = 300 / 20 = $15.00
        $compraData = [
            'proveedor_id' => $proveedor->id,
            'bodega_id' => $bodega->id,
            'numero_factura' => $numFactura,
            'fecha_emision' => now()->format('Y-m-d'),
            'observaciones' => 'Compra de prueba automatizada',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10,
                    'costo_unitario' => 20.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('compras.store'), $compraData);
        $response->assertRedirect(route('compras.index'));

        // Check stock updated to 20
        $inv = InventarioGeneral::where('producto_id', $producto->id)->where('bodega_id', $bodega->id)->first();
        $this->assertEquals(20, (float) $inv->stock_actual);

        // Check cost updated to 15.00
        $producto->refresh();
        $this->assertEquals(15.00, (float) $producto->costo_promedio);

        // Check Kardex entry
        $movDetalle = MovimientoDetalle::where('producto_id', $producto->id)
            ->whereHas('movimiento', function ($q) {
                $q->where('tipo_movimiento_id', 1); // 1 = COMPRA LOCAL
            })
            ->first();
        $this->assertNotNull($movDetalle);
        $this->assertEquals(10, (float) $movDetalle->cantidad);
        $this->assertEquals(20.00, (float) $movDetalle->costo_unitario);

        // Test Compra Show JSON
        $compra = Compra::where('numero_factura', $numFactura)->first();
        $this->assertNotNull($compra);
        $showRes = $this->actingAs($user)->getJson(route('compras.show', $compra->id));
        $showRes->assertOk();
        $showRes->assertJsonPath('compra.numero_factura', $numFactura);

        // Test Anular Compra (Reversal)
        $anularRes = $this->actingAs($user)->post(route('compras.anular', $compra->id), [
            'motivo' => 'Anulación de prueba de compra',
        ]);
        $anularRes->assertRedirect(route('compras.index'));

        // Check stock returned to 10
        $inv->refresh();
        $this->assertEquals(10, (float) $inv->stock_actual);
    }

    public function test_kardex_manual_adjustment_and_timeline(): void
    {
        $user = User::first();
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Central', 'codigo' => 'B01', 'estado' => 'ACTIVO']);
        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Test Cat', 'estado' => 'ACTIVO']);

        $codigo = 'AJUSTE_TEST_'.rand(10000, 99999);
        $producto = Producto::create([
            'codigo_principal' => $codigo,
            'nombre' => 'Producto Para Ajuste Test',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 5.00,
            'precio_unitario' => 10.00,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        InventarioGeneral::create([
            'producto_id' => $producto->id,
            'bodega_id' => $bodega->id,
            'stock_actual' => 15,
            'stock_minimo' => 2,
        ]);

        // Make negative adjustment (e.g. 3 damaged units => tipo_movimiento_id = 6 AJUSTE EGRESO)
        $ajusteData = [
            'producto_id' => $producto->id,
            'bodega_id' => $bodega->id,
            'tipo_movimiento_id' => 6, // 6 = AJUSTE EGRESO / MERMA
            'cantidad' => 3,
            'observaciones' => 'Merma por producto dañado en bodega',
        ];

        $response = $this->actingAs($user)->post(route('kardex.ajuste'), $ajusteData);
        $response->assertRedirect(route('kardex.index', ['producto_id' => $producto->id]));

        // Stock should now be 12
        $inv = InventarioGeneral::where('producto_id', $producto->id)->where('bodega_id', $bodega->id)->first();
        $this->assertEquals(12, (float) $inv->stock_actual);

        // Movimiento detalle should exist
        $movDetalle = MovimientoDetalle::where('producto_id', $producto->id)
            ->whereHas('movimiento', function ($q) {
                $q->where('tipo_movimiento_id', 6);
            })
            ->first();
        $this->assertNotNull($movDetalle);
        $this->assertEquals(3, (float) $movDetalle->cantidad);

        // Test Product Kardex timeline JSON endpoint
        $kardexRes = $this->actingAs($user)->getJson(route('kardex.producto', $producto->id));
        $kardexRes->assertOk();
        $kardexRes->assertJsonPath('success', true);
        $this->assertNotEmpty($kardexRes->json('movimientos'));
    }

    public function test_quick_ajax_category_and_supplier_creation(): void
    {
        $user = User::first();

        // Test fast category creation
        $catName = 'Categoria Express '.rand(1000, 9999);
        $responseCat = $this->actingAs($user)->postJson(route('productos.categoriaStore'), [
            'nombre' => $catName,
            'descripcion' => 'Descripción categoría express',
        ]);
        $responseCat->assertOk();
        $responseCat->assertJsonPath('success', true);
        $responseCat->assertJsonPath('categoria.nombre', $catName);

        // Test fast supplier creation (04 = RUC)
        $provRuc = '17'.rand(100000000, 999999999).'001';
        $responseProv = $this->actingAs($user)->postJson(route('compras.proveedorRapido'), [
            'razon_social' => 'Proveedor Express S.A.',
            'identificacion' => $provRuc,
            'tipo_identificacion' => '04',
            'telefono' => '0999888777',
            'correo' => 'express@proveedor.com',
            'direccion' => 'Av. Amazonas y Colón',
        ]);
        $responseProv->assertOk();
        $responseProv->assertJsonPath('success', true);
        $responseProv->assertJsonPath('proveedor.identificacion', $provRuc);
    }

    public function test_csv_exports(): void
    {
        $user = User::first();

        $resProd = $this->actingAs($user)->get(route('productos.export'));
        $resProd->assertOk();
        $this->assertTrue(str_contains($resProd->headers->get('content-type'), 'text/csv'));

        $resComp = $this->actingAs($user)->get(route('compras.export'));
        $resComp->assertOk();
        $this->assertTrue(str_contains($resComp->headers->get('content-type'), 'text/csv'));

        $resKard = $this->actingAs($user)->get(route('kardex.export'));
        $resKard->assertOk();
        $this->assertTrue(str_contains($resKard->headers->get('content-type'), 'text/csv'));
    }
}
