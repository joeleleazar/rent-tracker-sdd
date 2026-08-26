# Quickstart: Validar Simplificación de Navegación de Periodo (Recibos)

**Feature**: `028-simplificar-navegacion-periodo-recibos` | **Date**: 2026-08-26

## 1. Verificar el comportamiento

```bash
php artisan test --filter=RegistroMasivoRecibosControllerTest
```

Manualmente, en `/recibos/registro-masivo`: flechas navegan, campo de fecha autoenvía, sin botón "Ir" visible.

## 2. Revisión de diseño

`/impeccable polish` sobre `resources/views/recibos/registro-masivo/index.blade.php`.

## 3. Regresión general

```bash
php artisan test
```
