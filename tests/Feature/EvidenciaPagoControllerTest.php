<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Pago;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $this->inquilino = Inquilino::factory()->create();
    $this->contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
    ]);
    $this->recibo = Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'monto_renta' => '1000.00',
    ]);
    $this->pago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);
});

test('specs/035: subir una imagen como evidencia la asocia al pago', function () {
    $archivo = UploadedFile::fake()->image('comprobante-firmado.jpg')->size(500);

    $respuesta = $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), [
        'archivo' => $archivo,
    ]);

    $respuesta->assertRedirect(route('recibos.show', $this->recibo));
    $this->pago->refresh();
    expect($this->pago->tieneEvidencia())->toBeTrue();
    expect($this->pago->evidencia_tipo)->toBe('imagen');
    Storage::disk('local')->assertExists($this->pago->evidencia_ruta);
});

test('specs/035: subir un pdf como evidencia tambien es valido', function () {
    $archivo = UploadedFile::fake()->create('comprobante-firmado.pdf', 500, 'application/pdf');

    $respuesta = $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), [
        'archivo' => $archivo,
    ]);

    $respuesta->assertRedirect(route('recibos.show', $this->recibo));
    $this->pago->refresh();
    expect($this->pago->evidencia_tipo)->toBe('pdf');
});

test('specs/035: subir una evidencia nueva reemplaza la anterior', function () {
    $primerArchivo = UploadedFile::fake()->image('primero.jpg');
    $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), ['archivo' => $primerArchivo]);
    $rutaAnterior = $this->pago->fresh()->evidencia_ruta;

    $segundoArchivo = UploadedFile::fake()->image('segundo.jpg');
    $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), ['archivo' => $segundoArchivo]);

    $this->pago->refresh();
    expect($this->pago->evidencia_nombre_archivo)->toBe('segundo.jpg');
    Storage::disk('local')->assertMissing($rutaAnterior);
    Storage::disk('local')->assertExists($this->pago->evidencia_ruta);
});

test('specs/035: se puede consultar la evidencia ya subida', function () {
    $archivo = UploadedFile::fake()->image('comprobante-firmado.jpg');
    $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), ['archivo' => $archivo]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.evidencia.show', $this->pago));

    $respuesta->assertOk();
});

test('specs/035: consultar la evidencia de un pago que no tiene ninguna responde 404', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('pagos.evidencia.show', $this->pago));

    $respuesta->assertNotFound();
});

test('specs/035: rechaza un archivo de tipo no admitido', function () {
    $archivo = UploadedFile::fake()->create('comprobante.docx', 500, 'application/msword');

    $respuesta = $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), [
        'archivo' => $archivo,
    ]);

    $respuesta->assertSessionHasErrors('archivo');
    expect($this->pago->fresh()->tieneEvidencia())->toBeFalse();
});

test('specs/035: rechaza un archivo que excede el tamano maximo', function () {
    $archivo = UploadedFile::fake()->create('comprobante.pdf', 10241, 'application/pdf');

    $respuesta = $this->actingAs($this->admin)->post(route('pagos.evidencia.store', $this->pago), [
        'archivo' => $archivo,
    ]);

    $respuesta->assertSessionHasErrors('archivo');
});
