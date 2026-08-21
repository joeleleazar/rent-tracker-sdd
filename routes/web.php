<?php

use App\Http\Controllers\ConfiguracionGeneralController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\DocumentoContratoController;
use App\Http\Controllers\LecturaMedidorController;
use App\Http\Controllers\LocacionController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\RepresentanteController;
use App\Models\Locacion;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', ['locaciones' => Locacion::orderBy('nombre')->get()]);
    })->name('dashboard');

    Route::get('/locaciones', [LocacionController::class, 'index'])->name('locaciones.index');
    Route::get('/locaciones/crear', [LocacionController::class, 'create'])->name('locaciones.create');
    Route::post('/locaciones', [LocacionController::class, 'store'])->name('locaciones.store');
    Route::get('/locaciones/{locacion}', [LocacionController::class, 'show'])->name('locaciones.show');
    Route::get('/locaciones/{locacion}/editar', [LocacionController::class, 'edit'])->name('locaciones.edit');
    Route::put('/locaciones/{locacion}', [LocacionController::class, 'update'])->name('locaciones.update');
    Route::delete('/locaciones/{locacion}', [LocacionController::class, 'destroy'])->name('locaciones.destroy');

    Route::get('/locaciones/{locacion}/contratos', [ContratoController::class, 'index'])->name('contratos.index');
    Route::get('/locaciones/{locacion}/contratos/crear', [ContratoController::class, 'create'])->name('contratos.create');
    Route::post('/locaciones/{locacion}/contratos', [ContratoController::class, 'store'])->name('contratos.store');
    Route::get('/contratos/{contrato}', [ContratoController::class, 'show'])->name('contratos.show');
    Route::get('/contratos/{contrato}/editar', [ContratoController::class, 'edit'])->name('contratos.edit');
    Route::put('/contratos/{contrato}', [ContratoController::class, 'update'])->name('contratos.update');
    Route::patch('/contratos/{contrato}/costos', [ContratoController::class, 'actualizarCostos'])->name('contratos.costos.update');
    Route::post('/contratos/{contrato}/garantia/resolucion', [ContratoController::class, 'registrarResolucionGarantia'])->name('contratos.garantia.resolucion');

    Route::post('/contratos/{contrato}/documentos', [DocumentoContratoController::class, 'store'])->name('contratos.documentos.store');
    Route::get('/contratos/{contrato}/documentos/{documento}', [DocumentoContratoController::class, 'show'])->name('contratos.documentos.show');
    Route::delete('/contratos/{contrato}/documentos/{documento}', [DocumentoContratoController::class, 'destroy'])->name('contratos.documentos.destroy');

    Route::get('/representantes/buscar', [RepresentanteController::class, 'buscar'])->name('representantes.buscar');
    Route::post('/representantes', [RepresentanteController::class, 'store'])->name('representantes.store');
    Route::post('/contratos/{contrato}/representantes', [ContratoController::class, 'agregarRepresentante'])->name('contratos.representantes.store');
    Route::delete('/contratos/{contrato}/representantes/{representante}', [ContratoController::class, 'quitarRepresentante'])->name('contratos.representantes.destroy');

    // Recibos: rutas locación-céntricas (no contrato-céntricas), ver
    // specs/005-lecturas-medidor-recibo-periodo/research.md §1 — reconciliación
    // aplicada directamente en specs/004, documentada en su tasks.md.
    Route::get('/locaciones/{locacion}/recibos', [ReciboController::class, 'index'])->name('locaciones.recibos.index');
    Route::get('/locaciones/{locacion}/recibos/crear', [ReciboController::class, 'create'])->name('locaciones.recibos.create');
    Route::post('/locaciones/{locacion}/recibos', [ReciboController::class, 'store'])->name('locaciones.recibos.store');
    Route::get('/recibos/{recibo}', [ReciboController::class, 'show'])->name('recibos.show');
    Route::get('/recibos/{recibo}/editar', [ReciboController::class, 'edit'])->name('recibos.edit');
    Route::put('/recibos/{recibo}', [ReciboController::class, 'update'])->name('recibos.update');
    Route::patch('/recibos/{recibo}/estado', [ReciboController::class, 'actualizarEstado'])->name('recibos.estado.update');
    Route::get('/recibos/{recibo}/comprobante', [ReciboController::class, 'comprobante'])->name('recibos.comprobante');

    // Lecturas de medidor (specs/005-lecturas-medidor-recibo-periodo, US1/US3)
    Route::get('/locaciones/{locacion}/lecturas', [LecturaMedidorController::class, 'index'])->name('locaciones.lecturas.index');
    Route::get('/locaciones/{locacion}/lecturas/crear', [LecturaMedidorController::class, 'create'])->name('locaciones.lecturas.create');
    Route::post('/locaciones/{locacion}/lecturas', [LecturaMedidorController::class, 'store'])->name('locaciones.lecturas.store');
    Route::get('/lecturas/{lectura}/editar', [LecturaMedidorController::class, 'edit'])->name('lecturas.edit');
    Route::put('/lecturas/{lectura}', [LecturaMedidorController::class, 'update'])->name('lecturas.update');

    Route::get('/configuracion', [ConfiguracionGeneralController::class, 'edit'])->name('configuracion.edit');
    Route::put('/configuracion', [ConfiguracionGeneralController::class, 'update'])->name('configuracion.update');
});

require __DIR__.'/auth.php';
