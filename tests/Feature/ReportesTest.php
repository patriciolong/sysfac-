<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reportes_index_loads_all_tabs(): void
    {
        $user = User::first();

        $tabs = ['general', 'ventas', 'compras', 'inventario', 'caja', 'tributario'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($user)->get(route('reportes.index', ['tab' => $tab]));
            $response->assertOk();
            $response->assertSee('Centro de Reportes');
        }
    }

    public function test_reportes_date_filters(): void
    {
        $user = User::first();

        $presets = ['hoy', 'esta_semana', 'este_mes', 'mes_anterior', 'este_anio'];

        foreach ($presets as $rango) {
            $response = $this->actingAs($user)->get(route('reportes.index', ['rango' => $rango]));
            $response->assertOk();
        }

        // Custom range
        $resCustom = $this->actingAs($user)->get(route('reportes.index', [
            'rango' => 'personalizado',
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-09-18',
        ]));
        $resCustom->assertOk();
    }

    public function test_export_ventas_csv(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get(route('reportes.export.ventas'));
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_export_compras_csv(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get(route('reportes.export.compras'));
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_export_inventario_csv(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get(route('reportes.export.inventario'));
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_export_caja_csv(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get(route('reportes.export.caja'));
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_export_tributario_csv(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get(route('reportes.export.tributario'));
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }
}
