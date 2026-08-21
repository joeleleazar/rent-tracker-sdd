<?php

use App\Models\ConfiguracionGeneral;

test('la migracion siembra la fila unica id=1 con un correo por defecto', function () {
    expect(ConfiguracionGeneral::count())->toBe(1);
    expect(ConfiguracionGeneral::actual()->id)->toBe(1);
});

test('actual reutiliza la fila existente sin duplicarla', function () {
    $primera = ConfiguracionGeneral::actual();
    $primera->update(['correo_notificaciones_vencimiento' => 'admin@ejemplo.com']);

    $segunda = ConfiguracionGeneral::actual();

    expect($segunda->id)->toBe(1);
    expect($segunda->correo_notificaciones_vencimiento)->toBe('admin@ejemplo.com');
    expect(ConfiguracionGeneral::count())->toBe(1);
});

test('actual recrea la fila id=1 si fue eliminada manualmente', function () {
    ConfiguracionGeneral::actual()->delete();
    expect(ConfiguracionGeneral::count())->toBe(0);

    $configuracion = ConfiguracionGeneral::actual();

    expect($configuracion->id)->toBe(1);
    expect(ConfiguracionGeneral::count())->toBe(1);
});
