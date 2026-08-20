# Análisis de Specs para Vistas Intuitivas con Bootstrap 5

**Fecha**: 2026-08-19  
**Objetivo**: Identificar qué specs son esenciales para crear una interfaz de usuario intuitiva y accesible usando Bootstrap 5 con enfoque Senior-First.

---

## 📊 Resumen Ejecutivo

El proyecto Rent Tracker tiene **9 features** definidas que forman un ecosistema completo de gestión inmobiliaria. Se ha identificado una **secuencia de dependencias** clara que determina qué vistas deben implementarse primero para lograr una experiencia usuario coherente y funcional.

### Clasificación de Specs por Criticidad para la Interfaz

| Prioridad | Specs | Razón |
|-----------|-------|-------|
| 🔴 **CRÍTICA** (MVP mínimo) | 001, 002, 003, 004 | Sin estas, no hay datos base ni formularios fundamentales |
| 🟡 **IMPORTANTE** (Flujo completo) | 005, 007, 009 | Cierran el flujo de negocio (medidores, cobros, garantías) |
| 🟢 **COMPLEMENTARIA** (Mejoras) | 006, 008 | Historial, alertas y análisis avanzados |

---

## 🎯 Análisis Detallado por Spec

### **001 - JERARQUÍA DE LOCACIONES** 
**Estado MVP**: ✅ Fundamental  
**Prioridad Vista**: 🔴 **P0 - CRÍTICA**

#### Por qué es esencial:
- Es el **catálogo base** del negocio. Sin locaciones no hay nada que alquilar.
- Todas las demás features dependen de que exista una locación registrada.
- Define la **estructura organizativa** del sistema (Galería → Piso → Local).

#### Vistas Requeridas (Bootstrap):
1. **Dashboard de Locaciones** - Árbol jerárquico con breadcrumbs (Senior-First)
2. **Formulario de Creación/Edición** - Campos grandes, validación en tiempo real
3. **Tabla de Locaciones** - Filtros por "Alquilable/No Alquilable"
4. **Detalle de Locación** - Vista completa con contexto jerárquico

#### Requisitos UI/UX:
- Tipografía mínima: **18px**
- Contraste WCAG AA/AAA
- Botones táctiles: **48x48px mínimo**
- Breadcrumbs truncados máx 3 niveles
- Sin menús desplegables complejos

---

### **002 - GESTIÓN DE CONTRATOS**
**Estado MVP**: ✅ Fundamental  
**Prioridad Vista**: 🔴 **P0 - CRÍTICA**

#### Por qué es esencial:
- Es el **núcleo transaccional** del negocio.
- Implementa la restricción de "no solapamiento" que previene overbooking.
- Base para facturación y cobros posteriores.

#### Vistas Requeridas (Bootstrap):
1. **Formulario de Contrato** - Campos: inquilino, fechas, monto, documentos
2. **Validación de Solapamiento** - Modal de confirmación con alto contraste
3. **Historial de Contratos** - Timeline o tabla con diferenciación visual (Activo/Vencido)
4. **Carga de Documentos** - Área drag-drop o botones grandes para PDF/Fotos
5. **Galería de Documentos** - Visor de PDF/imágenes con zoom Senior-First

#### Requisitos UI/UX:
- Dropzone visible con área clara de clic (48x48px)
- Límites visuales claros: 1 PDF (15MB) O hasta 10 fotos (5MB c/u)
- Indicador de estado de descarga
- Modal de confirmación para cada eliminación

---

### **003 - REPRESENTANTES DE CONTRATO**
**Estado MVP**: ✅ Fundamental  
**Prioridad Vista**: 🔴 **P0 - CRÍTICA**

#### Por qué es esencial:
- Dato **obligatorio y mandatorio**: Todo contrato requiere ≥1 representante.
- Validación compleja: múltiples representantes con uno "principal".
- Directorio centralizado reutilizable.

#### Vistas Requeridas (Bootstrap):
1. **Panel de Representantes** - Lista editable con botón "Agregar Otro"
2. **Formulario de Representante** - Campos: apellidos, nombres, DNI, fecha nacimiento
3. **Búsqueda en Directorio** - Modal para reutilizar representantes existentes
4. **Indicador Principal** - Toggle/Radio button para designar principal
5. **Modal de Confirmación** - Para remover representantes

#### Requisitos UI/UX:
- Tarjetas grandes para cada representante (mínimo 200px de ancho)
- Botón "Marcar como Principal" con radio button visual
- Confirmación explícita antes de remover
- Búsqueda por DNI/Apellido con autocomplete
- Indicador visual claro del "Representante Principal"

---

### **004 - CONDICIONES DE CONTRATO Y COSTOS PARA RECIBOS**
**Estado MVP**: ✅ Fundamental  
**Prioridad Vista**: 🔴 **P0 - CRÍTICA**

#### Por qué es esencial:
- Define los **montos de referencia** que todos los recibos heredarán.
- Implementa notificaciones de vencimiento por correo (30, 15, 7 días).
- Base para el sistema de generación de recibos.

#### Vistas Requeridas (Bootstrap):
1. **Sección de Costos del Contrato** - Panel en el formulario con campos:
   - Costo de Renta (S/ 0.00)
   - Costo de Agua (S/ 0.00)
   - Costo de Luz (S/ 0.00)
   - Costo de Pasadizo (S/ 0.00)
   - Costo de Seguridad (S/ 0.00)

2. **Indicador de Vencimiento** - Badge con "Vence en X días"
3. **Vista de Configuración** - Email administrativo para notificaciones
4. **Historial de Notificaciones** - Log de cuáles ya se enviaron

#### Requisitos UI/UX:
- Campos monetarios con símbolo S/ integrado
- Spinner o incremento/decremento para números
- Validación: no permitir valores negativos
- Color rojo/naranja para contratos próximos a vencer
- Confirmación antes de cambiar fecha de fin (afecta hitos de notificación)

---

### **005 - LECTURAS DE MEDIDOR Y RECIBO POR PERIODO**
**Estado MVP**: ✅ Importante  
**Prioridad Vista**: 🟡 **P1 - IMPORTANTE**

#### Por qué es esencial:
- Cierra el **flujo de generación de recibos**.
- Sin lecturas de medidor, no hay base para calcular consumo de luz.
- Permite control detallado de costos variables.

#### Vistas Requeridas (Bootstrap):
1. **Tabla de Periodos (Meses)** - Lista de meses con lectura anterior/actual
2. **Formulario de Lectura** - Campos precargados:
   - Lectura Anterior (deshabilitada/informativa)
   - Lectura Actual (entrada numérica)
   - Consumo Calculado (automático, lectura-actual - lectura-anterior)

3. **Generador de Recibo** - Modal/formulario con:
   - Checkboxes para incluir/excluir conceptos
   - Campos editables para cada monto
   - Resumen total actualizado en tiempo real

4. **Vista Previa de Recibo** - PDF/Imagen generada antes de emitir

#### Requisitos UI/UX:
- Tabla responsive con desplazamiento horizontal en móvil
- Spinner para entrada de lecturas (no solo texto)
- Checkboxes grandes (24x24px mínimo) para conceptos
- Cálculo de consumo mostrado prominentemente
- Botón "Emitir Recibo" con confirmación

---

### **006 - HISTORIAL DE LECTURA DE MEDIDOR**
**Estado MVP**: 🟢 Complementaria  
**Prioridad Vista**: 🟢 **P2 - COMPLEMENTARIA**

#### Por qué es importante:
- **Auditoría y trazabilidad** del consumo histórico.
- Detección de anomalías (consumos que bajan sospechosamente).
- Respaldo ante reclamos de inquilinos.

#### Vistas Requeridas (Bootstrap):
1. **Gráfico de Consumo Histórico** - Línea o barras mostrando tendencia
2. **Tabla de Historial Completo** - Lectura anterior, actual, consumo, fecha
3. **Indicador de Cambios** - Si se editó lectura anterior de un periodo

#### Requisitos UI/UX:
- Gráfico responsive (Chart.js o similar)
- Tabla con scroll vertical para muchos meses
- Tooltip al pasar sobre valores anómalos
- Color diferente para meses sin contrato activo

---

### **007 - ESTADO Y ENVÍO DE RECIBOS**
**Estado MVP**: ✅ Importante  
**Prioridad Vista**: 🟡 **P1 - IMPORTANTE**

#### Por qué es esencial:
- **Control de cobros**: Saber qué recibos están pendientes, pagados, anulados.
- Integración con WhatsApp para entrega digital.
- Reducción de uso de papel.

#### Vistas Requeridas (Bootstrap):
1. **Panel de Estados de Recibo** - Botones de estado:
   - 🟡 Pendiente (amarillo)
   - 🟢 Pagado (verde)
   - ❌ Anulado (rojo)

2. **Listado de Recibos** - Tabla filtrable por estado
3. **Detalle de Recibo** - Vista completa con:
   - Información de concepto/monto
   - Botones: "Marcar como Pagado", "Anular", "Enviar WhatsApp", "Imprimir"

4. **Generador de Imagen** - Para WhatsApp (formato PNG/JPG)
5. **Vista de Impresión** - CSS de print adaptada

#### Requisitos UI/UX:
- Badges de color para cada estado (18px mínimo)
- Botones distintivos:
  - "Marcar como Pagado" (Verde, 48x48px)
  - "Anular Recibo" (Rojo, requiere confirmación)
  - "Enviar WhatsApp" (Verde con icono WhatsApp)
  - "Imprimir" (Icono impresora)
- Modal de confirmación para cualquier cambio de estado
- Indicador visual de fecha de pago (cuando se marcó como pagado)

---

### **008 - PRORRATEO, ALERTAS Y FECHA LÍMITE DE PAGO**
**Estado MVP**: 🟢 Complementaria  
**Prioridad Vista**: 🟢 **P2 - COMPLEMENTARIA**

#### Por qué es importante:
- **Inteligencia de negocio**: Cálculo automático de prorrateos.
- **Alertas operativas**: Recordatorios de fechas límite de pago.
- **Precisión en cobros**: Evita sobre/subcobros.

#### Vistas Requeridas (Bootstrap):
1. **Panel de Configuración** - Input para días de anticipación (número)
2. **Centro de Alertas** - Listado de alertas pendientes del mes
3. **Sugerencia de Prorrateo** - En formulario de recibo:
   - "17 días de 31 activos" (informativo)
   - Monto sugerido editable (en rojo si se sugiere ajuste)

4. **Historial de Alertas Enviadas** - Tabla de cuáles se enviaron y cuándo

#### Requisitos UI/UX:
- Spinner/Input para días de anticipación (1-31)
- Alertas mostradas en rojo/naranja si están próximas
- Sugerencia de prorrateo con explicación en tooltip
- Toggle para "Usar prorrateo sugerido" vs ingreso manual

---

### **009 - GARANTÍA DE CONTRATO**
**Estado MVP**: ✅ Importante  
**Prioridad Vista**: 🟡 **P1 - IMPORTANTE**

#### Por qué es esencial:
- **Datos financieros críticos**: Monto de garantía entregada.
- **Resolución al finalizar contrato**: Devolución/retención de garantía.
- **Auditoría**: Justificación de retenciones por daños.

#### Vistas Requeridas (Bootstrap):
1. **Sección de Garantía en Contrato** - Campos:
   - Monto Entregado (S/ 0.00)
   - Fecha de Entrega (date picker)
   - Medio de Entrega (select: Efectivo/Depósito/Otro)
   - Estado: Sin Registrar / Registrada / Resuelta

2. **Formulario de Resolución** - Modal/formulario con:
   - Monto Devuelto (S/ 0.00)
   - Monto Retenido (S/ 0.00)
   - Motivo de Retención (text area, obligatorio si retiene)
   - Validación: Devuelto + Retenido = Garantía Entregada

3. **Vista de Detalle** - Mostrar estado actual y fechas

#### Requisitos UI/UX:
- Badge de color para estado (Verde si resuelta, Naranja si pendiente)
- Campos monetarios con validación en tiempo real
- Validación cruzada: si retiene algo, motivo es obligatorio
- Confirmación antes de marcar como "Resuelta"
- Historial editable (botón "Corregir Resolución")

---

## 🏗️ Arquitectura de Vistas Sugerida

### **Estructura de Navegación (Bootstrap Navbar)**

```
┌─────────────────────────────────────────────────┐
│ Rent Tracker              [Login] [Settings]    │
├─────────────────────────────────────────────────┤
│ ▶ Locaciones  ▶ Contratos  ▶ Recibos  ▶ Reportes │
└─────────────────────────────────────────────────┘
```

### **Wireframe de Locaciones (P0)**
```
┌─────────────────────────────────────────────────┐
│ Galería El Sol > Piso 1 > Local A (Alquilable)  │
├─────────────────────────────────────────────────┤
│ [+ Nueva Locación] [Editar] [Ver Contratos]     │
├─────────────────────────────────────────────────┤
│ Tamaño: 120.5 m²                                │
│ Ubicación: Sector Norte                         │
│ Descripción: Primer nivel de la galería         │
│ Alquilable: ✓ Sí                                │
└─────────────────────────────────────────────────┘
```

### **Wireframe de Contratos (P0)**
```
┌────────────────────────────────────────────────────┐
│ Contrato - Local A                                 │
├────────────────────────────────────────────────────┤
│ Inquilino: [Dropdown]  Desde: [Date]  Hasta: [Date]│
│ Monto: S/ [1500.00]                                │
├────────────────────────────────────────────────────┤
│ REPRESENTANTES (Mínimo 1 requerido)                │
│ ┌──────────────────────────────────────────────┐   │
│ │ Juan Pérez Gómez (DNI 12345678)              │   │
│ │ [Marcar Principal]  [Remover]                │   │
│ └──────────────────────────────────────────────┘   │
│ [+ Agregar Otro Representante]                     │
├────────────────────────────────────────────────────┤
│ COSTOS DE REFERENCIA                               │
│ Renta: S/ [1500.00]     Agua: S/ [50.00]           │
│ Luz: S/ [80.00]         Pasadizo: S/ [30.00]       │
│ Seguridad: S/ [40.00]                              │
├────────────────────────────────────────────────────┤
│ GARANTÍA                                           │
│ Monto: S/ [1500.00]  Fecha: [2026-08-19]           │
│ Medio: [Efectivo ▼]                                │
├────────────────────────────────────────────────────┤
│ DOCUMENTOS                                         │
│ [Seleccionar PDF] o [Subir Fotos]                  │
│ Documentos: pdf-contrato.pdf (✓)                   │
└────────────────────────────────────────────────────┘
```

### **Wireframe de Recibos (P1)**
```
┌────────────────────────────────────────────────┐
│ Recibos - Local A (Agosto 2026)                │
├────────────────────────────────────────────────┤
│ Estado: [🟡 Pendiente ▼] Fecha Pago: -         │
│ [Marcar Pagado] [Anular] [WhatsApp] [Imprimir]│
├────────────────────────────────────────────────┤
│ Concepto              │ Cantidad │ Monto      │
│───────────────────────┼──────────┼────────────│
│ Alquiler              │ 1        │ S/ 1500.00 │
│ Luz (150 unidades)    │ 150      │ S/ 75.00   │
│ Agua                  │ 1        │ S/ 50.00   │
│ Seguridad             │ 1        │ S/ 40.00   │
│───────────────────────┼──────────┼────────────│
│ TOTAL                 │          │ S/ 1665.00 │
└────────────────────────────────────────────────┘
```

---

## 📋 Componentes Bootstrap Recomendados

### **Formularios (004)**
- `form-group` con `form-label` grande (18px)
- `form-control` con `form-control-lg`
- `input-group` para campos monetarios (S/)
- `spinner-border` para validaciones asincrónicas

### **Tablas (005, 006, 007)**
- `.table-responsive` para móvil
- `.table-hover` para interactividad
- Badges para estados (`.badge-success`, `.badge-warning`, `.badge-danger`)

### **Modales (003, 007, 009)**
- `.modal` con tamaño `.modal-lg` para formularios
- `.btn-close-white` en header oscuro
- Botones confirmación en footer: `.btn-primary` + `.btn-secondary`

### **Iconografía**
- Bootstrap Icons v1.11+:
  - `check-circle-fill` (Pagado)
  - `exclamation-circle-fill` (Pendiente)
  - `x-circle-fill` (Anulado)
  - `printer-fill` (Imprimir)
  - `whatsapp` (Enviar)

### **Alerts (Notificaciones)**
- `.alert-info` para información general
- `.alert-warning` para vencimientos próximos
- `.alert-danger` para errores de validación

---

## 🎨 Paleta de Colores Senior-First

| Elemento | Color | Contraste |
|----------|-------|-----------|
| Fondo Principal | #F8F9FA | ✓ Gris muy claro |
| Texto | #212529 | ✓✓✓ Negro fuerte |
| Primario (Acciones) | #0D6EFD | ✓✓ Azul Bootstrap |
| Éxito (Pagado) | #198754 | ✓✓ Verde |
| Advertencia (Pendiente) | #FFC107 | ✓ Amarillo con texto oscuro |
| Peligro (Anulado) | #DC3545 | ✓✓ Rojo claro |
| Bordes | #DEE2E6 | ✓ Gris suave |

---

## 🚀 Plan de Implementación de Vistas

### **Fase 1: MVP Funcional (Specs 001-004)**
**Objetivo**: Poder registrar locaciones, contratos, representantes y costos.

1. Dashboard de Locaciones (árbol jerárquico)
2. CRUD de Locaciones (Create, Read, Update, Delete)
3. CRUD de Contratos (con validación de solapamiento)
4. Panel de Representantes (búsqueda + agregar múltiples)
5. Sección de Costos y Garantía

**Dependencias**: Modelos y validaciones de DB

**Tiempo Estimado**: 3-4 semanas

---

### **Fase 2: Sistema de Recibos (Specs 005-007)**
**Objetivo**: Generar, enviar e imprimir recibos.

1. Registro de Lecturas de Medidor
2. Generador de Recibos con conceptos configurables
3. Gestor de Estados de Recibos
4. Envío por WhatsApp + Impresión

**Dependencias**: Fase 1 completada

**Tiempo Estimado**: 2-3 semanas

---

### **Fase 3: Inteligencia Operativa (Specs 006, 008)**
**Objetivo**: Historial, alertas y análisis.

1. Gráfico de Consumo Histórico
2. Centro de Alertas de Vencimiento
3. Cálculo de Prorrateos

**Dependencias**: Fase 2 completada

**Tiempo Estimado**: 2 semanas

---

## ✅ Checklist de Bootstrap 5 Compliance

- [ ] Tipografía base: `font-size-base: 1.125rem` (18px)
- [ ] Espaciado: usar `$spacer` (1rem = 16px) y múltiplos
- [ ] Botones: mínimo 48x48px de área táctil
- [ ] Inputs: altura mínima 44px
- [ ] Colores: Verificar contraste WCAG AA (4.5:1 mínimo)
- [ ] Responsive: `.container-fluid` con breakpoints `lg: 1200px`
- [ ] Iconos: Bootstrap Icons v1.11+
- [ ] Validación: `.is-invalid` / `.is-valid` en tiempo real
- [ ] Accesibilidad: `aria-label`, `aria-describedby`, `role` atributos
- [ ] Impresión: Media queries `@media print { ... }`

---

## 🎯 Conclusiones y Recomendación

### **Para un MVP Intuitivo con Bootstrap 5:**

✅ **Specs ESENCIALES (Implementar Primero)**:
1. **001** - Jerarquía de Locaciones
2. **002** - Gestión de Contratos
3. **003** - Representantes
4. **004** - Condiciones de Contrato
5. **007** - Estado y Envío de Recibos (para cerrar flujo básico)

⚠️ **Specs RECOMENDADAS (Segunda Fase)**:
- **005** - Lecturas de Medidor
- **009** - Garantía de Contrato

🟢 **Specs COMPLEMENTARIAS (Tercera Fase)**:
- **006** - Historial de Medidor
- **008** - Prorrateo y Alertas

### **Stack Tecnológico Sugerido:**

```
Backend:
- Laravel 13.x
- PHP 8.4+
- PostgreSQL 15+

Frontend:
- Bootstrap 5.3.3
- Alpine.js (interactividad ligera, Senior-First)
- Chart.js (gráficos de consumo)
- Tabler Icons (iconografía completa)

DevOps:
- Docker Compose (desarrollo)
- Tailwind CSS (complementario a Bootstrap)
```

### **Principios de Diseño No Negociables:**

1. **Senior-First**: Tipografía 18px+, botones 48x48px, alto contraste
2. **Sin complejidad innecesaria**: Evitar menús complejos, popovers, etc.
3. **Accesibilidad WCAG 2.1 AA**: Ratios de contraste, etiquetas, roles
4. **Transaccionalidad**: Cada acción importante con confirmación
5. **Transparencia de datos**: Mostrar claramente qué se va a guardar/modificar

---

**Documento generado por análisis de especificaciones del 2026-08-19**
