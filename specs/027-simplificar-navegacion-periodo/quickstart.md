# Quickstart: Validar Simplificación de Navegación de Periodo

**Feature**: `027-simplificar-navegacion-periodo` | **Date**: 2026-08-26

## 1. Verificar el comportamiento (FR-001 a FR-004)

```bash
php artisan test --filter=RegistroMasivoLecturasControllerTest
```

Manualmente, en `/lecturas/registro-masivo`:
- Clic en ‹ y en › → el periodo cambia correctamente.
- Cambiar el campo de fecha → el periodo cambia automáticamente, sin botón adicional.
- Confirmar que no hay ningún botón "Ir" visible.
- Cambiar el periodo y confirmar que la tarifa por kWh y los enlaces de exportar no se ven afectados.

## 2. Verificar la revisión de diseño (Principio VI)

Ejecutar `/impeccable polish` (o `audit`) sobre `resources/views/lecturas/registro-masivo/index.blade.php`.

## 3. Regresión general

```bash
php artisan test
```

Ninguna aserción existente debe cambiar de resultado esperado, salvo la prueba específica del botón "Ir" (FR-005).
