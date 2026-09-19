<?php

namespace Tests\Feature;

use App\Models\CajaTurno;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ImpresionTermicaTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_descargar_servidor_impresion_exe(): void
    {
        $response = $this->actingAs($this->user)->get(route('facturacion.descargarServidor'));
        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition') ?? '', 'SysFact_Printer.exe') ||
            $response->headers->get('content-type') === 'application/x-msdownload' ||
            $response->headers->get('content-type') === 'application/octet-stream'
        );
    }

    public function test_get_factura_ticket_data(): void
    {
        $factura = Factura::first();
        if (! $factura) {
            $this->markTestSkipped('No hay facturas registradas en la base de datos para la prueba.');
        }

        $response = $this->actingAs($this->user)->get(route('facturacion.ticketData', $factura->id));
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'ticket_data' => [
                'type',
                'comprobante',
                'clave_acceso',
                'fecha_emision',
                'emisor' => ['ruc', 'razon_social'],
                'cliente' => ['razon_social', 'identificacion'],
                'detalles',
                'totales',
                'pagos',
                'open_drawer',
            ],
        ]);
        $this->assertEquals('factura', $response->json('ticket_data.type'));
    }

    public function test_get_factura_ticket_html_view(): void
    {
        $factura = Factura::first();
        if (! $factura) {
            $this->markTestSkipped('No hay facturas registradas en la base de datos para la prueba.');
        }

        $response = $this->actingAs($this->user)->get(route('facturacion.ticketHtml', $factura->id));
        $response->assertOk();
        $response->assertSee('Imprimir Ticket');
        $response->assertSee($factura->establecimiento.'-'.$factura->punto_emision.'-'.$factura->secuencial);
    }

    public function test_get_caja_turno_ticket_data(): void
    {
        $turno = CajaTurno::first();
        if (! $turno) {
            $this->markTestSkipped('No hay turnos de caja registrados.');
        }

        $response = $this->actingAs($this->user)->get(route('caja.turnoTicketData', $turno->id));
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'ticket_data' => [
                'type',
                'turno_id',
                'caja',
                'usuario',
                'monto_inicial',
                'total_ventas',
                'saldo_teorico',
            ],
        ]);
        $this->assertEquals('cierre_caja', $response->json('ticket_data.type'));
    }

    public function test_get_caja_turno_ticket_html_view(): void
    {
        $turno = CajaTurno::first();
        if (! $turno) {
            $this->markTestSkipped('No hay turnos de caja registrados.');
        }

        $response = $this->actingAs($this->user)->get(route('caja.turnoTicketHtml', $turno->id));
        $response->assertOk();
        $response->assertSee('CIERRE / ARQUEO DE CAJA');
    }
}
