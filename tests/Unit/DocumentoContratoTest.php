<?php

use App\Models\Contrato;
use App\Models\DocumentoContrato;

test('un documento de contrato pertenece a un contrato', function () {
    $contrato = Contrato::factory()->create();
    $documento = DocumentoContrato::factory()->create(['contrato_id' => $contrato->id]);

    expect($documento->contrato)->toBeInstanceOf(Contrato::class);
    expect($documento->contrato->id)->toBe($contrato->id);
});

test('al eliminar un contrato se eliminan en cascada sus documentos', function () {
    $contrato = Contrato::factory()->create();
    $documento = DocumentoContrato::factory()->create(['contrato_id' => $contrato->id]);

    $contrato->delete();

    expect(DocumentoContrato::find($documento->id))->toBeNull();
});
