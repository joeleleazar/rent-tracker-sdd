<?php

use App\Http\Controllers\ConceptoGastoFijoController;
use App\Http\Controllers\ConfiguracionGeneralController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\ControladorCobroQr;
use App\Http\Controllers\ControladorPanelInicio;
use App\Http\Controllers\ControladorUsuario;
use App\Http\Controllers\DocumentoContratoController;
use App\Http\Controllers\EvidenciaPagoController;
use App\Http\Controllers\InquilinoController;
use App\Http\Controllers\LecturaMedidorController;
use App\Http\Controllers\LocacionController;
use App\Http\Controllers\PagoReciboController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\RegistroMasivoLecturasController;
use App\Http\Controllers\RegistroMasivoRecibosController;
use App\Http\Controllers\SeguimientoPagosController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // specs/043-panel-inicio-cobranza: la ruta de inicio post-login pasa de ser
    // un alias hacia locaciones.index (specs/013) a renderizar el panel de
    // cobranza de solo lectura (morosos, próximos vencimientos, indicadores).
    // El nombre `dashboard` se conserva porque AuthenticatedSessionController y
    // la ruta raíz redirigen a él.
    Route::get('/dashboard', [ControladorPanelInicio::class, 'index'])->name('dashboard');

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
    Route::post('/locaciones/{locacion}/recibos/borrador', [ReciboController::class, 'guardarBorrador'])->name('locaciones.recibos.borrador');

    // Registro masivo de recibos (specs/023): DEBE registrarse antes de
    // /recibos/{recibo} (más abajo) — de lo contrario Laravel intenta bindear
    // "registro-masivo" como {recibo} y falla con un error de tipo en la BD
    // antes de llegar siquiera a este controlador.
    Route::get('/recibos/registro-masivo', [RegistroMasivoRecibosController::class, 'index'])->name('recibos.registroMasivo.index');
    // specs/044 (US2): carga masiva de recibos por plantilla. Rutas literales
    // registradas antes de /recibos/{recibo} y de /recibos/registro-masivo/{locacion}/recibos.
    Route::get('/recibos/registro-masivo/plantilla', [RegistroMasivoRecibosController::class, 'plantilla'])->name('recibos.registroMasivo.plantilla');
    Route::post('/recibos/registro-masivo/importar/previsualizar', [RegistroMasivoRecibosController::class, 'previsualizarImportacion'])->name('recibos.registroMasivo.importar.previsualizar');
    Route::post('/recibos/registro-masivo/importar/confirmar', [RegistroMasivoRecibosController::class, 'confirmarImportacion'])->name('recibos.registroMasivo.importar.confirmar');
    Route::get('/recibos/registro-masivo/{locacion}/recibos', [RegistroMasivoRecibosController::class, 'recibosDelPeriodo'])->name('recibos.registroMasivo.recibosDelPeriodo');

    Route::get('/recibos/{recibo}', [ReciboController::class, 'show'])->name('recibos.show');
    Route::get('/recibos/{recibo}/editar', [ReciboController::class, 'edit'])->name('recibos.edit');
    Route::put('/recibos/{recibo}', [ReciboController::class, 'update'])->name('recibos.update');
    Route::patch('/recibos/{recibo}/estado', [ReciboController::class, 'actualizarEstado'])->name('recibos.estado.update');
    Route::get('/recibos/{recibo}/comprobante', [ReciboController::class, 'comprobante'])->name('recibos.comprobante');

    // specs/032: pagos de un recibo — anidada bajo /recibos/{recibo} para registrar,
    // pero direccionada por su propio id (/pagos/{pago}) para editar/eliminar.
    Route::post('/recibos/{recibo}/pagos', [PagoReciboController::class, 'store'])->name('pagos.store');
    Route::put('/pagos/{pago}', [PagoReciboController::class, 'update'])->name('pagos.update');
    Route::delete('/pagos/{pago}', [PagoReciboController::class, 'destroy'])->name('pagos.destroy');
    Route::get('/pagos/{pago}/comprobante', [PagoReciboController::class, 'comprobante'])->name('pagos.comprobante');
    Route::post('/pagos/{pago}/evidencia', [EvidenciaPagoController::class, 'store'])->name('pagos.evidencia.store');
    Route::get('/pagos/{pago}/evidencia', [EvidenciaPagoController::class, 'show'])->name('pagos.evidencia.show');

    // specs/032: pantalla de seguimiento de pagos — misma jerarquía de locales que
    // recibos.registroMasivo.index (research.md Decisión 6). Registrada antes de
    // /pagos/{pago} (más abajo, cuando exista) por el mismo motivo ya documentado
    // para /recibos/registro-masivo.
    Route::get('/pagos/seguimiento', [SeguimientoPagosController::class, 'index'])->name('pagos.seguimiento.index');

    // specs/044 (US3): cobro por QR desde el inicio. `cobro.recibo` (destino del
    // QR del comprobante y del ingreso manual) va FIRMADA — un id crudo no debe
    // abrir el formulario de pago (FR-023/FR-030). El resto solo hereda auth +
    // cuenta.activa: Master y Administrador comparten esta pila (research.md D10).
    Route::get('/cobro', [ControladorCobroQr::class, 'index'])->name('cobro.index');
    Route::get('/cobro/buscar', [ControladorCobroQr::class, 'buscar'])->name('cobro.buscar');
    Route::get('/cobro/recibo/{recibo}', [ControladorCobroQr::class, 'recibo'])->middleware('signed')->name('cobro.recibo');
    Route::post('/cobro/recibo/{recibo}/pago', [ControladorCobroQr::class, 'registrarPago'])->name('cobro.pago.store');

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

    // specs/044 (US1): carga masiva de lecturas por plantilla. Vía adicional a
    // la grilla manual y a la exportación de specs/015; rutas literales, todas
    // antes de /lecturas/{lectura} (arriba) por el mismo motivo ya documentado.
    Route::get('/lecturas/registro-masivo/plantilla', [RegistroMasivoLecturasController::class, 'plantilla'])->name('lecturas.registroMasivo.plantilla');
    Route::post('/lecturas/registro-masivo/importar/previsualizar', [RegistroMasivoLecturasController::class, 'previsualizarImportacion'])->name('lecturas.registroMasivo.importar.previsualizar');
    Route::post('/lecturas/registro-masivo/importar/confirmar', [RegistroMasivoLecturasController::class, 'confirmarImportacion'])->name('lecturas.registroMasivo.importar.confirmar');

    // Catálogo de conceptos de gasto fijo (specs/024)
    Route::get('/conceptos-gasto-fijo', [ConceptoGastoFijoController::class, 'index'])->name('conceptosGastoFijo.index');
    Route::get('/conceptos-gasto-fijo/crear', [ConceptoGastoFijoController::class, 'create'])->name('conceptosGastoFijo.create');
    Route::post('/conceptos-gasto-fijo', [ConceptoGastoFijoController::class, 'store'])->name('conceptosGastoFijo.store');
    Route::get('/conceptos-gasto-fijo/{conceptosGastoFijo}/editar', [ConceptoGastoFijoController::class, 'edit'])->name('conceptosGastoFijo.edit');
    Route::put('/conceptos-gasto-fijo/{conceptosGastoFijo}', [ConceptoGastoFijoController::class, 'update'])->name('conceptosGastoFijo.update');
    Route::delete('/conceptos-gasto-fijo/{conceptosGastoFijo}', [ConceptoGastoFijoController::class, 'destroy'])->name('conceptosGastoFijo.destroy');

    Route::get('/configuracion', [ConfiguracionGeneralController::class, 'edit'])->name('configuracion.edit');
    Route::put('/configuracion', [ConfiguracionGeneralController::class, 'update'])->name('configuracion.update');

    // specs/040: gestión de usuarios por perfiles — sección exclusiva del
    // perfil Master (middleware `perfil.master`), además de `auth` y
    // `cuenta.activa` heredados del grupo. Un Administrador que conozca
    // cualquiera de estas URLs recibe 403.
    Route::middleware('perfil.master')->prefix('usuarios')->name('usuarios.')->controller(ControladorUsuario::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{usuario}/editar', 'edit')->name('edit');
        Route::put('/{usuario}', 'update')->name('update');
        Route::put('/{usuario}/contrasena', 'restablecerContrasena')->name('contrasena.update');
        Route::put('/{usuario}/perfil', 'cambiarPerfil')->name('perfil.update');
        Route::put('/{usuario}/estado', 'cambiarEstado')->name('estado.update');
        Route::delete('/{usuario}', 'destroy')->name('destroy');
    });
});

require __DIR__.'/auth.php';
