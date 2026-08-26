# Quickstart: Completar Total en Importación Histórica y Seeder

Escenarios de validación manual — esta feature no tiene test Pest dedicado (ver plan.md, Constitution Check,
Principio IV). Usar el binario de PHP de Herd en esta máquina:
`C:\Users\joel5\.config\herd\bin\php.bat`.

## Escenario 1 — Seeder de demostración corre sin error

1. Sobre una base de datos de desarrollo (o una nueva, vacía): `php artisan db:seed` (o `migrate:fresh --seed`).
2. **Resultado esperado**: el comando termina en éxito, sin `SQLSTATE[23502]` (violación `NOT NULL`) ni
   ningún otro error de base de datos.
3. Verificar: las 3 lecturas de medidor del "Local 101" existen, ninguna con `total` nulo, y cada una es
   igual a `consumo × 0.85` (contracts/calculo-total.md, Contrato 2).

## Escenario 2 — Comando de importación histórica corre sin error

1. Con un `extracted.json` de ejemplo en `storage/app/private/import_medidores/` (formato ya definido por el
   comando, ver `codigo`/`periodo`/`lectura_actual`/... por registro).
2. Ejecutar `php artisan medidores:importar-historico`.
3. **Resultado esperado**: el comando termina en éxito, sin error de base de datos; el resumen final reporta
   la cantidad de locaciones e inquilinos creados como antes de este fix (sin cambios en esa parte del
   comportamiento).
4. Verificar: `App\Models\LecturaMedidor::whereNull('total')->count()` devuelve `0` sobre las lecturas recién
   importadas.

## Verificación ya realizada (2026-08-25, antes de escribir esta spec)

Como parte de la corrección original ("regulariza todo ahora"), ya se confirmó directamente contra
`rent_tracker_dev` (consulta de solo lectura, sin modificar datos):

```
Total de lecturas: 15
Lecturas con total NULL: 0
Lecturas con total = 0: 0
Locaciones "Historico Medidores": 0
```

Es decir: no había datos irregulares que corregir (el comando de importación nunca se había corrido en este
entorno), y la suite completa de Pest (256/256) seguía en verde tras aplicar el fix — sin regresiones en los
dos flujos que sí tienen test dedicado (`LecturaMedidorControllerTest`, `RegistroMasivoLecturasControllerTest`).
