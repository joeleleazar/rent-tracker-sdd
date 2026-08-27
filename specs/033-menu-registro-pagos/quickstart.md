# Quickstart: Menú de Registro de Pagos en la Jerarquía de Locales

Validación manual, a correr tras implementar. Usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) y el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener al menos una locación con un recibo vigente del periodo actual con saldo pendiente (para ver
   "Registrar Pago"), y otra con un recibo vigente ya pagado por completo (para ver que "Registrar Pago" no
   aparece pero "Ver Pagos" sí).

## Escenario 1 — Llegar por el menú (US1)

1. Desde cualquier pantalla del sistema, abrir el menú principal.
2. Confirmar que existe un ítem "Registro de Pagos" junto a Locaciones, Gestionar Locaciones, Registrar
   Lecturas, Emitir Recibos, Conceptos de Gasto Fijo y Configuración.
3. Hacer clic en "Registro de Pagos" y confirmar que el título visible de la página es "Registro de Pagos"
   y que el ítem de menú queda señalado como la sección activa.

## Escenario 2 — Registrar un pago desde la fila (US2)

1. En "Registro de Pagos", localizar la fila de una locación con saldo pendiente en el periodo.
2. Confirmar que ofrece una acción "Registrar Pago".
3. Hacer clic y confirmar que llega directo a la pantalla del recibo (con su formulario de registrar pago
   visible), sin pasos intermedios, cuando la locación tiene un único recibo vigente.
4. Repetir sobre una locación con más de un recibo vigente en el periodo y confirmar que primero se muestra
   un selector de recibos, igual que ya ocurre con "Ver Recibos"/"Ver Pagos".
5. Confirmar que una locación cuyos recibos del periodo ya están completamente pagados NO ofrece "Registrar
   Pago".

## Escenario 3 — Revisar pagos sin registrar uno nuevo (US3)

1. Sobre una locación con recibos vigentes en el periodo (con o sin saldo pendiente), confirmar que sigue
   existiendo una acción separada para ver los pagos ya registrados ("Ver Pagos"), independiente de
   "Registrar Pago".

## Casos límite a revisar

- Locación sin ningún recibo emitido en el periodo: ni "Registrar Pago" ni la acción de revisión aparecen.
- Locación cuyo único recibo del periodo está anulado: tampoco aparece ninguna de las dos acciones.
- Locación con varios recibos vigentes, unos pagados y otros no: "Registrar Pago" sigue disponible.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- La pantalla de avance de pagos (specs/032) no pierde ninguna de sus columnas (Nombre/Locación, Estado de
  Pago, Avance, Acción) ni su selector de periodo.
- "Emitir Recibos" y su propio "Generar Recibo"/"Ver Recibos" no cambian de comportamiento.
