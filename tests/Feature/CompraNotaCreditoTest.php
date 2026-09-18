<?php

namespace Tests\Feature;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\CompraNotaCredito;
use App\Models\InventarioGeneral;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompraNotaCreditoTest extends TestCase
{
    use DatabaseTransactions;

    private function setupTestData(): array
    {
        $user = User::first();
        $categoria = Categoria::first() ?? Categoria::create(['nombre' => 'Test Cat', 'estado' => 'ACTIVO']);
        $bodega = Bodega::first() ?? Bodega::create(['nombre' => 'Bodega Central', 'codigo' => 'B01', 'estado' => 'ACTIVO']);
        $proveedor = Proveedor::first() ?? Proveedor::create([
            'razon_social' => 'Proveedor NC Test S.A.',
            'identificacion' => '17'.rand(100000000, 999999999).'001',
            'tipo_identificacion' => '04',
            'estado' => 'ACTIVO',
        ]);

        $prodA = Producto::create([
            'codigo_principal' => 'PROD_NC_A_'.rand(1000, 9999),
            'nombre' => 'Producto NC Test A',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 10.00,
            'precio_unitario' => 15.00,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        $prodB = Producto::create([
            'codigo_principal' => 'PROD_NC_B_'.rand(1000, 9999),
            'nombre' => 'Producto NC Test B',
            'categoria_id' => $categoria->id,
            'tipo_producto' => 'BIEN',
            'costo_promedio' => 20.00,
            'precio_unitario' => 30.00,
            'codigo_iva' => '2',
            'estado' => 'ACTIVO',
        ]);

        // Initial inventory: 10 of A, 5 of B
        InventarioGeneral::create([
            'producto_id' => $prodA->id,
            'bodega_id' => $bodega->id,
            'stock_actual' => 10,
            'stock_minimo' => 2,
        ]);

        InventarioGeneral::create([
            'producto_id' => $prodB->id,
            'bodega_id' => $bodega->id,
            'stock_actual' => 5,
            'stock_minimo' => 2,
        ]);

        // Create Purchase
        $numFactura = '001-001-'.rand(100000000, 999999999);
        $compra = Compra::create([
            'proveedor_id' => $proveedor->id,
            'bodega_id' => $bodega->id,
            'usuario_id' => 1,
            'numero_factura' => $numFactura,
            'fecha_emision' => now()->format('Y-m-d'),
            'subtotal_sin_impuestos' => 200.00,
            'iva' => 30.00,
            'total' => 230.00,
            'observaciones' => 'Factura de compra para test de NC',
        ]);

        CompraDetalle::create([
            'compra_id' => $compra->id,
            'producto_id' => $prodA->id,
            'cantidad' => 10,
            'costo_unitario' => 10.00,
            'costo_total' => 100.00,
        ]);

        CompraDetalle::create([
            'compra_id' => $compra->id,
            'producto_id' => $prodB->id,
            'cantidad' => 5,
            'costo_unitario' => 20.00,
            'costo_total' => 100.00,
        ]);

        return compact('user', 'categoria', 'bodega', 'proveedor', 'prodA', 'prodB', 'compra');
    }

    public function test_compra_detalles_endpoint_returns_valid_quantities(): void
    {
        $data = $this->setupTestData();

        $res = $this->actingAs($data['user'])->getJson(route('compras.notas-credito.compra-detalles', $data['compra']->id));
        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('compra.numero_factura', $data['compra']->numero_factura);

        $items = collect($res->json('compra.items'));
        $itemA = $items->firstWhere('producto_id', $data['prodA']->id);
        $itemB = $items->firstWhere('producto_id', $data['prodB']->id);

        $this->assertEquals(10, (float) $itemA['cantidad_disponible']);
        $this->assertEquals(5, (float) $itemB['cantidad_disponible']);
    }

    public function test_store_nota_credito_reduces_stock_and_logs_kardex(): void
    {
        $data = $this->setupTestData();
        $ncNum = '001-001-'.rand(100000000, 999999999);

        // Return 4 units of Prod A
        $payload = [
            'compra_id' => $data['compra']->id,
            'numero_nota_credito' => $ncNum,
            'autorizacion_sri' => str_pad(rand(1, 99999999), 49, '0', STR_PAD_LEFT),
            'fecha_emision' => now()->format('Y-m-d'),
            'motivo' => 'Devolución de 4 unidades por empaque deteriorado',
            'tipo_modificacion' => 'DEVOLUCION_MERCADERIA',
            'observaciones' => 'NC emitida por proveedor',
            'detalles' => [
                [
                    'producto_id' => $data['prodA']->id,
                    'cantidad' => 4,
                    'costo_unitario' => 10.00,
                ],
            ],
        ];

        $res = $this->actingAs($data['user'])->post(route('compras.notas-credito.store'), $payload);
        $res->assertRedirect(route('compras.notas-credito.index'));

        // 1. Verify CompraNotaCredito created
        $nc = CompraNotaCredito::where('numero_nota_credito', $ncNum)->first();
        $this->assertNotNull($nc);
        $this->assertEquals('EMITIDA', $nc->estado);
        $this->assertEquals(40.00, (float) $nc->subtotal_sin_impuestos);
        $this->assertEquals(46.00, (float) $nc->total); // 40 + 15% IVA = 46

        // 2. Verify stock reduced from 10 to 6
        $invA = InventarioGeneral::where('producto_id', $data['prodA']->id)->where('bodega_id', $data['bodega']->id)->first();
        $this->assertEquals(6, (float) $invA->stock_actual);

        // 3. Verify Kardex movement created with type 4 (DEVOLUCION COMPRA)
        $movDet = MovimientoDetalle::where('producto_id', $data['prodA']->id)
            ->whereHas('movimiento', function ($q) {
                $q->where('tipo_movimiento_id', 4);
            })
            ->first();
        $this->assertNotNull($movDet);
        $this->assertEquals(4, (float) $movDet->cantidad);

        // 4. Verify Show endpoint
        $showRes = $this->actingAs($data['user'])->getJson(route('compras.notas-credito.show', $nc->id));
        $showRes->assertOk();
        $showRes->assertJsonPath('nota_credito.numero_nota_credito', $ncNum);

        // 5. Verify available quantities on purchase are now updated
        $compraRes = $this->actingAs($data['user'])->getJson(route('compras.notas-credito.compra-detalles', $data['compra']->id));
        $items = collect($compraRes->json('compra.items'));
        $itemA = $items->firstWhere('producto_id', $data['prodA']->id);
        $this->assertEquals(6, (float) $itemA['cantidad_disponible']);
    }

    public function test_anular_nota_credito_reverses_stock(): void
    {
        $data = $this->setupTestData();
        $ncNum = '001-001-'.rand(100000000, 999999999);

        // Create NC returning 3 units of Prod B (stock goes 5 -> 2)
        $payload = [
            'compra_id' => $data['compra']->id,
            'numero_nota_credito' => $ncNum,
            'fecha_emision' => now()->format('Y-m-d'),
            'motivo' => 'Devolución test',
            'tipo_modificacion' => 'DEVOLUCION_MERCADERIA',
            'detalles' => [
                [
                    'producto_id' => $data['prodB']->id,
                    'cantidad' => 3,
                    'costo_unitario' => 20.00,
                ],
            ],
        ];

        $this->actingAs($data['user'])->post(route('compras.notas-credito.store'), $payload);

        $nc = CompraNotaCredito::where('numero_nota_credito', $ncNum)->first();
        $this->assertNotNull($nc);

        $invB = InventarioGeneral::where('producto_id', $data['prodB']->id)->where('bodega_id', $data['bodega']->id)->first();
        $this->assertEquals(2, (float) $invB->stock_actual);

        // Now Anulate the NC
        $anulRes = $this->actingAs($data['user'])->post(route('compras.notas-credito.anular', $nc->id), [
            'motivo' => 'Proveedor rechazó la devolución',
        ]);
        $anulRes->assertRedirect(route('compras.notas-credito.index'));

        $nc->refresh();
        $this->assertEquals('ANULADA', $nc->estado);

        // Stock must be restored back to 5
        $invB->refresh();
        $this->assertEquals(5, (float) $invB->stock_actual);
    }

    public function test_index_view_renders_successfully(): void
    {
        $user = User::first();
        $res = $this->actingAs($user)->get(route('compras.notas-credito.index'));
        $res->assertOk();
        $res->assertSee('Notas de Crédito de Compras');
        $res->assertSee('NCs Emitidas (Mes)');
        $res->assertSee('Monto Acreditado (Mes)');
    }

    public function test_export_notas_credito_csv(): void
    {
        $user = User::first();
        $res = $this->actingAs($user)->get(route('compras.notas-credito.export'));
        $res->assertOk();
        $this->assertTrue(str_contains($res->headers->get('content-type'), 'text/csv'));
    }
}
