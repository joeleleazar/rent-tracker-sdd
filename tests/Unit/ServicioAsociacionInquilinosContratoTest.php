<?php

use App\Exceptions\ContratoSinInquilinosException;
use App\Exceptions\InquilinoPrincipalInvalidoException;
use App\Exceptions\InquilinoPrincipalSinReemplazoException;
use App\Exceptions\UltimoInquilinoException;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Services\ServicioAsociacionInquilinosContrato;

beforeEach(function () {
    $this->servicio = new ServicioAsociacionInquilinosContrato();
});

test('sincronizar rechaza un contrato sin ningun inquilino', function () {
    $contrato = Contrato::factory()->create();
    $contrato->inquilinos()->detach();

    expect(fn () => $this->servicio->sincronizar($contrato, []))
        ->toThrow(ContratoSinInquilinosException::class);
});

test('sincronizar marca automaticamente como principal al unico inquilino', function () {
    $contrato = Contrato::factory()->create();
    $contrato->inquilinos()->detach();

    $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '12345678', 'fecha_nacimiento' => '1960-05-15'],
    ]);

    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(1);
    expect($contrato->inquilinos->first()->pivot->es_principal)->toBeTrue();
    expect(Inquilino::where('dni', '12345678')->exists())->toBeTrue();
});

test('sincronizar reutiliza un inquilino existente por dni en vez de duplicarlo', function () {
    $existente = Inquilino::factory()->create(['dni' => '11112222']);
    $contrato = Contrato::factory()->create();
    $contrato->inquilinos()->detach();

    $this->servicio->sincronizar($contrato, [
        ['apellidos' => $existente->apellidos, 'nombres' => $existente->nombres, 'dni' => '11112222', 'fecha_nacimiento' => $existente->fecha_nacimiento->format('Y-m-d')],
    ]);

    expect(Inquilino::where('dni', '11112222')->count())->toBe(1);
    expect($contrato->inquilinos()->first()->id)->toBe($existente->id);
});

test('sincronizar rechaza multiples inquilinos sin exactamente un principal', function () {
    $contrato = Contrato::factory()->create();
    $contrato->inquilinos()->detach();

    expect(fn () => $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15', 'es_principal' => false],
        ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15', 'es_principal' => false],
    ]))->toThrow(InquilinoPrincipalInvalidoException::class);

    expect(fn () => $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15', 'es_principal' => true],
        ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15', 'es_principal' => true],
    ]))->toThrow(InquilinoPrincipalInvalidoException::class);
});

test('sincronizar acepta multiples inquilinos con exactamente un principal', function () {
    $contrato = Contrato::factory()->create();
    $contrato->inquilinos()->detach();

    $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15', 'es_principal' => true],
        ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15', 'es_principal' => false],
    ]);

    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(2);
    expect($contrato->inquilinos()->wherePivot('es_principal', true)->count())->toBe(1);
});

test('agregar asocia un nuevo inquilino a un contrato existente', function () {
    $contrato = Contrato::factory()->create();
    $contrato->inquilinos()->detach();
    $this->servicio->sincronizar($contrato, [
        ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15'],
    ]);

    $this->servicio->agregar($contrato, [
        'apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15',
    ]);

    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(2);
});

test('quitar bloquea la remocion del unico inquilino del contrato', function () {
    $contrato = Contrato::factory()->create();
    $inquilino = $contrato->inquilinos()->first();

    expect(fn () => $this->servicio->quitar($contrato, $inquilino))
        ->toThrow(UltimoInquilinoException::class);

    expect($contrato->inquilinos()->count())->toBe(1);
});

test('quitar remueve un inquilino no principal sin exigir reemplazo', function () {
    $contrato = Contrato::factory()->create();
    $principal = $contrato->inquilinos()->first();
    $secundario = Inquilino::factory()->create();
    $contrato->inquilinos()->attach($secundario->id, ['es_principal' => false]);

    $this->servicio->quitar($contrato, $secundario);

    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(1);
    expect($contrato->inquilinos->first()->id)->toBe($principal->id);
});

test('quitar bloquea la remocion del principal sin designar un reemplazo', function () {
    $contrato = Contrato::factory()->create();
    $principal = $contrato->inquilinos()->first();
    $secundario = Inquilino::factory()->create();
    $contrato->inquilinos()->attach($secundario->id, ['es_principal' => false]);

    expect(fn () => $this->servicio->quitar($contrato, $principal))
        ->toThrow(InquilinoPrincipalSinReemplazoException::class);

    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(2);
});

test('quitar remueve al principal cuando se designa un reemplazo valido', function () {
    $contrato = Contrato::factory()->create();
    $principal = $contrato->inquilinos()->first();
    $secundario = Inquilino::factory()->create();
    $contrato->inquilinos()->attach($secundario->id, ['es_principal' => false]);

    $this->servicio->quitar($contrato, $principal, $secundario->id);

    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(1);
    expect($contrato->inquilinos()->wherePivot('es_principal', true)->count())->toBe(1);
    expect($contrato->inquilinos->first()->id)->toBe($secundario->id);
});
