<?php

use App\Http\Controllers\ConceptoGastoFijoController;
use App\Http\Controllers\ConfiguracionGeneralController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\DocumentoContratoController;
use App\Http\Controllers\InquilinoController;
use App\Http\Controllers\LecturaMedidorController;
use App\Http\Controllers\LocacionController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\RegistroMasivoLecturasController;
use App\Http\Controllers\RegistroMasivoRecibosController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    // specs/013-arbol-jerarquico-locaciones: el listado general de locaciones
    // se consolidó en locaciones.index (árbol jerárquico unificado); esta ruta
    // se conserva como alias de navegación post-login en vez de eliminarse.
    Route::get('/dashboard', function () {
        return redirect()->route('locaciones.index');
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

    Route::get('/inquilinos/buscar', [InquilinoController::class, 'buscar'])->name('inquilinos.buscar');
    Route::post('/inquilinos', [InquilinoController::class, 'store'])->name('inquilinos.store');
    Route::post('/contratos/{contrato}/inquilinos', [ContratoController::class, 'agregarInquilino'])->name('contratos.inquilinos.store');
    Route::delete('/contratos/{contrato}/inquilinos/{inquilino}', [ContratoController::class, 'quitarInquilino'])->name('contratos.inquilinos.destroy');

    // Recibos: rutas locación-céntricas (no contrato-céntricas), ver
    // specs/005-lecturas-medidor-recibo-periodo/research.md §1 — reconciliación
    // aplicada directamente en specs/004, documentada en su tasks.md.
    Route::get('/locaciones/{locacion}/recibos', [ReciboController::class, 'index'])->name('locaciones.recibos.index');
    Route::get('/locaciones/{locacion}/recibos/crear', [ReciboController::class, 'create'])->name('locaciones.recibos.create');
    Route::post('/locaciones/{locacion}/recibos', [ReciboController::class, 'store'])->name('locaciones.recibos.store');

    // Registro masivo de recibos (specs/023): DEBE registrarse antes de
    // /recibos/{recibo} (más abajo) — de lo contrario Laravel intenta bindear
    // "registro-masivo" como {recibo} y falla con un error de tipo en la BD
    // antes de llegar siquiera a este controlador.
    Route::get('/recibos/registro-masivo', [RegistroMasivoRecibosController::class, 'index'])->name('recibos.registroMasivo.index');
    Route::get('/recibos/registro-masivo/{locacion}/modal', [RegistroMasivoRecibosController::class, 'modal'])->name('recibos.registroMasivo.modal');
    Route::post('/recibos/registro-masivo/{locacion}', [RegistroMasivoRecibosController::class, 'store'])->name('recibos.registroMasivo.store');

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

    // Registro masivo de lecturas de luz (specs/015): vía adicional al flujo
    // individual de arriba, para completar varias locaciones a la vez.
    Route::get('/lecturas/registro-masivo', [RegistroMasivoLecturasController::class, 'index'])->name('lecturas.registroMasivo.index');
    Route::post('/lecturas/registro-masivo', [RegistroMasivoLecturasController::class, 'store'])->name('lecturas.registroMasivo.store');
    Route::post('/lecturas/registro-masivo/borrador', [RegistroMasivoLecturasController::class, 'guardarBorrador'])->name('lecturas.registroMasivo.borrador');
    Route::patch('/lecturas/registro-masivo/tarifa', [RegistroMasivoLecturasController::class, 'actualizarTarifa'])->name('lecturas.registroMasivo.actualizarTarifa');
    Route::get('/lecturas/registro-masivo/exportar/excel', [RegistroMasivoLecturasController::class, 'exportarExcel'])->name('lecturas.registroMasivo.exportarExcel');
    Route::get('/lecturas/registro-masivo/exportar/pdf', [RegistroMasivoLecturasController::class, 'exportarPdf'])->name('lecturas.registroMasivo.exportarPdf');
    Route::get('/lecturas/registro-masivo/lecturas/{lectura}/editar-inline', [RegistroMasivoLecturasController::class, 'editarInline'])->name('lecturas.registroMasivo.editarInline');
    Route::patch('/lecturas/registro-masivo/lecturas/{lectura}', [RegistroMasivoLecturasController::class, 'actualizarInline'])->name('lecturas.registroMasivo.actualizarInline');

    // Catálogo de conceptos de gasto fijo (specs/024)
    Route::get('/conceptos-gasto-fijo', [ConceptoGastoFijoController::class, 'index'])->name('conceptosGastoFijo.index');
    Route::get('/conceptos-gasto-fijo/crear', [ConceptoGastoFijoController::class, 'create'])->name('conceptosGastoFijo.create');
    Route::post('/conceptos-gasto-fijo', [ConceptoGastoFijoController::class, 'store'])->name('conceptosGastoFijo.store');
    Route::get('/conceptos-gasto-fijo/{conceptosGastoFijo}/editar', [ConceptoGastoFijoController::class, 'edit'])->name('conceptosGastoFijo.edit');
    Route::put('/conceptos-gasto-fijo/{conceptosGastoFijo}', [ConceptoGastoFijoController::class, 'update'])->name('conceptosGastoFijo.update');
    Route::delete('/conceptos-gasto-fijo/{conceptosGastoFijo}', [ConceptoGastoFijoController::class, 'destroy'])->name('conceptosGastoFijo.destroy');

    Route::get('/configuracion', [ConfiguracionGeneralController::class, 'edit'])->name('configuracion.edit');
    Route::put('/configuracion', [ConfiguracionGeneralController::class, 'update'])->name('configuracion.update');
});

require __DIR__.'/auth.php';
