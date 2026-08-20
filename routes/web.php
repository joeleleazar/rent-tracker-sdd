<?php

use App\Http\Controllers\ContratoController;
use App\Http\Controllers\DocumentoContratoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/locaciones/{locacion}/contratos', [ContratoController::class, 'index'])->name('contratos.index');
    Route::get('/locaciones/{locacion}/contratos/crear', [ContratoController::class, 'create'])->name('contratos.create');
    Route::post('/locaciones/{locacion}/contratos', [ContratoController::class, 'store'])->name('contratos.store');
    Route::get('/contratos/{contrato}', [ContratoController::class, 'show'])->name('contratos.show');
    Route::get('/contratos/{contrato}/editar', [ContratoController::class, 'edit'])->name('contratos.edit');
    Route::put('/contratos/{contrato}', [ContratoController::class, 'update'])->name('contratos.update');

    Route::post('/contratos/{contrato}/documentos', [DocumentoContratoController::class, 'store'])->name('contratos.documentos.store');
    Route::get('/contratos/{contrato}/documentos/{documento}', [DocumentoContratoController::class, 'show'])->name('contratos.documentos.show');
    Route::delete('/contratos/{contrato}/documentos/{documento}', [DocumentoContratoController::class, 'destroy'])->name('contratos.documentos.destroy');
});

require __DIR__.'/auth.php';
