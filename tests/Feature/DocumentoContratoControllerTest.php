<?php

use App\Models\Contrato;
use App\Models\DocumentoContrato;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->create();
    $this->contrato = Contrato::factory()->create();
});

test('un administrador autenticado puede subir un pdf del contrato', function () {
    $archivo = UploadedFile::fake()->create('contrato.pdf', 2000, 'application/pdf');

    $respuesta = $this->actingAs($this->admin)->post(
        route('contratos.documentos.store', $this->contrato),
        ['archivo_pdf' => $archivo]
    );

    $respuesta->assertRedirect(route('contratos.show', $this->contrato));
    expect(DocumentoContrato::where('contrato_id', $this->contrato->id)->where('tipo_archivo', 'pdf')->count())->toBe(1);

    $documento = DocumentoContrato::firstWhere('contrato_id', $this->contrato->id);
    Storage::disk('local')->assertExists($documento->ruta_archivo);
});

test('un administrador autenticado puede subir varias fotos del contrato', function () {
    $fotos = [
        UploadedFile::fake()->image('pagina1.jpg')->size(1000),
        UploadedFile::fake()->image('pagina2.jpg')->size(1000),
    ];

    $respuesta = $this->actingAs($this->admin)->post(
        route('contratos.documentos.store', $this->contrato),
        ['archivo_imagenes' => $fotos]
    );

    $respuesta->assertRedirect(route('contratos.show', $this->contrato));
    expect(DocumentoContrato::where('contrato_id', $this->contrato->id)->where('tipo_archivo', 'imagen')->count())->toBe(2);
});

test('rechaza un pdf que excede el limite de tamano', function () {
    $archivo = UploadedFile::fake()->create('contrato.pdf', 15361, 'application/pdf');

    $respuesta = $this->actingAs($this->admin)->post(
        route('contratos.documentos.store', $this->contrato),
        ['archivo_pdf' => $archivo]
    );

    $respuesta->assertSessionHasErrors('archivo_pdf');
    expect(DocumentoContrato::where('contrato_id', $this->contrato->id)->count())->toBe(0);
});

test('rechaza un tipo de archivo no permitido', function () {
    $archivo = UploadedFile::fake()->create('contrato.txt', 100, 'text/plain');

    $respuesta = $this->actingAs($this->admin)->post(
        route('contratos.documentos.store', $this->contrato),
        ['archivo_pdf' => $archivo]
    );

    $respuesta->assertSessionHasErrors('archivo_pdf');
});

test('rechaza mezclar pdf y fotos en el mismo contrato', function () {
    DocumentoContrato::factory()->create([
        'contrato_id' => $this->contrato->id,
        'tipo_archivo' => 'pdf',
    ]);

    $foto = UploadedFile::fake()->image('pagina1.jpg')->size(1000);

    $respuesta = $this->actingAs($this->admin)->post(
        route('contratos.documentos.store', $this->contrato),
        ['archivo_imagenes' => [$foto]]
    );

    $respuesta->assertSessionHasErrors();
    expect(DocumentoContrato::where('contrato_id', $this->contrato->id)->count())->toBe(1);
});

test('un administrador autenticado puede transmitir el documento', function () {
    Storage::disk('local')->put('contratos/1/contrato.pdf', 'contenido-pdf');
    $documento = DocumentoContrato::factory()->create([
        'contrato_id' => $this->contrato->id,
        'ruta_archivo' => 'contratos/1/contrato.pdf',
        'nombre_archivo' => 'contrato.pdf',
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('contratos.documentos.show', [$this->contrato, $documento]));

    $respuesta->assertOk();
});

test('la transmision del documento requiere autenticacion', function () {
    $documento = DocumentoContrato::factory()->create(['contrato_id' => $this->contrato->id]);

    $respuesta = $this->get(route('contratos.documentos.show', [$this->contrato, $documento]));

    $respuesta->assertRedirect(route('login'));
});

test('un administrador autenticado puede eliminar un documento', function () {
    Storage::disk('local')->put('contratos/1/contrato.pdf', 'contenido');
    $documento = DocumentoContrato::factory()->create([
        'contrato_id' => $this->contrato->id,
        'ruta_archivo' => 'contratos/1/contrato.pdf',
    ]);

    $respuesta = $this->actingAs($this->admin)->delete(route('contratos.documentos.destroy', [$this->contrato, $documento]));

    $respuesta->assertRedirect(route('contratos.show', $this->contrato));
    expect(DocumentoContrato::find($documento->id))->toBeNull();
    Storage::disk('local')->assertMissing('contratos/1/contrato.pdf');
});
