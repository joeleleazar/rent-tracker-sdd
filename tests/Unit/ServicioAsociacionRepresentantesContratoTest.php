<?php

use App\Exceptions\ContratoSinRepresentantesException;
use App\Exceptions\RepresentantePrincipalInvalidoException;
use App\Exceptions\UltimoRepresentanteException;
use App\Models\Contrato;
use App\Models\Representante;
use App\Services\ServicioAsociacionRepresentantesContrato;

beforeEach(function () {
    $this->servicio = new ServicioAsociacionRepresentantesContrato();
});

test('sincronizar rechaza un contrato sin ningun representante', function () {
    $contrato = Contrato::factory()->create();

    expect(fn () => $this->servicio->sincronizar($contrato, []))
        ->toThrow(ContratoSinRepresentantesException::class);
});

test('sincronizar marca automaticamente como principal al unico representante', function () {
    $contrato = Contrato::factory()->create();

    $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '12345678', 'fecha_nacimiento' => '1960-05-15'],
    ]);

    $contrato->refresh();
    expect($contrato->representantes)->toHaveCount(1);
    expect($contrato->representantes->first()->pivot->es_principal)->toBeTrue();
    expect(Representante::where('dni', '12345678')->exists())->toBeTrue();
});

test('sincronizar reutiliza un representante existente por dni en vez de duplicarlo', function () {
    $existente = Representante::factory()->create(['dni' => '11112222']);
    $contrato = Contrato::factory()->create();

    $this->servicio->sincronizar($contrato, [
        ['apellidos' => $existente->apellidos, 'nombres' => $existente->nombres, 'dni' => '11112222', 'fecha_nacimiento' => $existente->fecha_nacimiento->format('Y-m-d')],
    ]);

    expect(Representante::where('dni', '11112222')->count())->toBe(1);
    expect($contrato->representantes()->first()->id)->toBe($existente->id);
});

test('sincronizar rechaza multiples representantes sin exactamente un principal', function () {
    $contrato = Contrato::factory()->create();

    expect(fn () => $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15', 'es_principal' => false],
        ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15', 'es_principal' => false],
    ]))->toThrow(RepresentantePrincipalInvalidoException::class);

    expect(fn () => $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15', 'es_principal' => true],
        ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15', 'es_principal' => true],
    ]))->toThrow(RepresentantePrincipalInvalidoException::class);
});

test('sincronizar acepta multiples representantes con exactamente un principal', function () {
    $contrato = Contrato::factory()->create();

    $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15', 'es_principal' => true],
        ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15', 'es_principal' => false],
    ]);

    $contrato->refresh();
    expect($contrato->representantes)->toHaveCount(2);
    expect($contrato->representantes()->wherePivot('es_principal', true)->count())->toBe(1);
});

test('agregar asocia un nuevo representante a un contrato existente', function () {
    $contrato = Contrato::factory()->create();
    $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15'],
    ]);

    $this->servicio->agregar($contrato, [
        'apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15',
    ]);

    $contrato->refresh();
    expect($contrato->representantes)->toHaveCount(2);
});

test('quitar bloquea la remocion del unico representante del contrato', function () {
    $contrato = Contrato::factory()->create();
    $representante = Representante::factory()->create();
    $contrato->representantes()->attach($representante->id, ['es_principal' => true]);

    expect(fn () => $this->servicio->quitar($contrato, $representante))
        ->toThrow(UltimoRepresentanteException::class);

    expect($contrato->representantes()->count())->toBe(1);
});

test('quitar remueve un representante cuando hay mas de uno y reasigna principal si hace falta', function () {
    $contrato = Contrato::factory()->create();
    $principal = Representante::factory()->create();
    $secundario = Representante::factory()->create();
    $contrato->representantes()->attach($principal->id, ['es_principal' => true]);
    $contrato->representantes()->attach($secundario->id, ['es_principal' => false]);

    $this->servicio->quitar($contrato, $principal);

    $contrato->refresh();
    expect($contrato->representantes)->toHaveCount(1);
    expect($contrato->representantes()->wherePivot('es_principal', true)->count())->toBe(1);
    expect($contrato->representantes->first()->id)->toBe($secundario->id);
});
