# RESUMEN EJECUTIVO: Specs para Vistas Bootstrap 5

**Análisis Completado**: 2026-08-19  
**Proyecto**: Rent Tracker  
**Objetivo**: Identificar specs esenciales para renderizar interfaz intuitiva con Bootstrap 5

---

## 🎯 RESPUESTA DIRECTA

### ¿Qué specs necesitas para renderizar vistas intuitivas con Bootstrap 5?

**CRÍTICOS (No puedes saltartelos):**
1. ✅ **SPEC 001** - Jerarquía de Locaciones
2. ✅ **SPEC 002** - Gestión de Contratos
3. ✅ **SPEC 003** - Representantes de Contrato
4. ✅ **SPEC 004** - Condiciones de Contrato y Costos

**IMPORTANTES (Cierran el flujo):**
5. ✅ **SPEC 005** - Lecturas de Medidor y Recibos
6. ✅ **SPEC 007** - Estado y Envío de Recibos
7. ✅ **SPEC 009** - Garantía de Contrato

**COMPLEMENTARIOS (Nice-to-have):**
8. 🟢 SPEC 006 - Historial de Medidor
9. 🟢 SPEC 008 - Prorrateo y Alertas

---

## 📊 Tabla Comparativa: ¿Qué renderiza cada spec?

| Spec | Feature | Componentes UI | Prioridad | Dependencias |
|------|---------|---|---|---|
| **001** | Jerarquía de Locaciones | Breadcrumbs, Árbol jerárquico, Tabla de locaciones | 🔴 P0 | NINGUNA |
| **002** | Gestión de Contratos | Formulario de contrato, Validación solapamiento, Carga de PDF/fotos, Timeline histórico | 🔴 P0 | 001 |
| **003** | Representantes | Panel de representantes, Búsqueda en directorio, Modal de agregar/remover | 🔴 P0 | 002 |
| **004** | Condiciones y Costos | Formulario de costos (renta, agua, luz, pasadizo, seguridad), Indicador de vencimiento | 🔴 P0 | 002 |
| **005** | Lecturas y Recibos | Tabla de periodos, Formulario de lectura, Generador de recibos, Vista previa | 🟡 P1 | 001, 002, 004 |
| **006** | Historial de Medidor | Gráfico de consumo, Tabla histórica, Indicadores de anomalías | 🟢 P2 | 005 |
| **007** | Estado y Envío | Selector de estado, Tabla de recibos, Botones WhatsApp/Imprimir, Vista de impresión | 🟡 P1 | 005 |
| **008** | Prorrateo y Alertas | Panel de alertas, Centro de configuración, Sugerencia de prorrateo | 🟢 P2 | 005, 007 |
| **009** | Garantía | Sección de garantía, Formulario de resolución, Validación de montos | 🟡 P1 | 002 |

---

## 🏗️ Árbol de Dependencias (CRITICAL PATH)

```
SPEC 001: Jerarquía de Locaciones (ROOT)
    ↓
SPEC 002: Gestión de Contratos
    ├─→ SPEC 003: Representantes ◄────┐
    ├─→ SPEC 004: Condiciones/Costos   │
    └─→ SPEC 009: Garantía             │
         ↓                              │
SPEC 005: Lecturas de Medidor & Recibos
         ├─→ SPEC 006: Historial (Optional)
         └─→ SPEC 007: Estado & Envío
              └─→ SPEC 008: Prorrateo (Optional)

┌─────────────────────────────────┐
│ RUTA CRÍTICA (Flujo Completo)   │
│ Duración: 6-8 semanas           │
│ Specs: 001→002→003→004→005→007  │
└─────────────────────────────────┘
```

---

## 📱 WIREFRAMES CONCEPTUALES

### Navegación Principal (Navbar Bootstrap)

```
┌────────────────────────────────────────────────────────────┐
│ 🏠 Rent Tracker  │  ⚙️  👤 Admin  │  🔐 Logout              │
├────────────────────────────────────────────────────────────┤
│  📍 Locaciones  │  📋 Contratos  │  💰 Recibos  │  📊 Reportes │
└────────────────────────────────────────────────────────────┘
```

### Dashboard de Inicio (Pos Phase 1)

```
┌─────────────────────────────────────────────────────────────┐
│                     RENT TRACKER                             │
│                   Dashboard - Agosto 2026                    │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ 📍 LOCACIONES   │  │ 📋 CONTRATOS    │  │ 💰 RECIBOS   │ │
│  │                 │  │                 │  │              │ │
│  │  12 Total       │  │  8 Activos      │  │ Pendientes:  │ │
│  │  ├─ 8 Alquilables│ │  ├─ 2 Próximas  │  │  S/ 24,500   │ │
│  │  └─ 4 Contenedores│ │  └─ 3 Próximas  │  │              │ │
│  │                 │  │                 │  │ Pagados:     │ │
│  │ [Ver Todas] ➜   │  │ [Gestionar] ➜  │  │ S/ 18,750    │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ ⚠️  Alertas y Vencimientos Próximos                   │   │
│  │                                                        │   │
│  │ 🔴 Contrato "Local A" vence en 7 días (15 ago)      │   │
│  │ 🟡 Pago límite en 5 días (último sábado del mes)    │   │
│  │ 🟢 Garantía de "Local B" pendiente de resolución    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Vista de Locaciones (SPEC 001)

```
┌─────────────────────────────────────────────────────────────┐
│  📍 Locaciones                              [+ Nueva] [Filtrar]│
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Galería El Sol                          [Editar] [Ver]  │
│  │ 500 m² | Sector Centro                                │
│  │ ├─ Piso 1                                             │
│  │ │  250 m² | Sector Norte                             │
│  │ │  ├─ Local A (✓ Alquilable)  [Contratos...]        │
│  │ │  ├─ Local B (✓ Alquilable)  [Contratos...]        │
│  │ │  └─ Almacén (✗ No Alquilable)                     │
│  │ │                                                      │
│  │ └─ Piso 2                                             │
│  │    250 m² | Sector Sur                               │
│  │    ├─ Local C (✓ Alquilable)  [Contratos...]        │
│  │    └─ Local D (✓ Alquilable)  [Contratos...]        │
│  │                                                        │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Vista de Contratos (SPEC 002-004)

```
┌──────────────────────────────────────────────────────────────┐
│  📋 Contratos - Local A              [+ Nuevo Contrato]      │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  CONTRATO ACTIVO                          [Editar] [Rescindir]│
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Contrato ID: 2026-006                                │   │
│  │ Inquilino: Carlos Rodríguez Pérez                   │   │
│  │ Fecha: 2026-06-01 → 2026-12-31 (Quedan 137 días)   │   │
│  │ Monto: S/ 1500.00/mes                               │   │
│  │                                                       │   │
│  │ 👥 Representantes:                                  │   │
│  │    ★ Juan Carlos Pérez (Principal)                 │   │
│  │       María Rosa López (Coapelante)                │   │
│  │                                                       │   │
│  │ 💰 Costos de Referencia:                            │   │
│  │    Renta: S/ 1500.00  │  Agua: S/ 50.00            │   │
│  │    Luz: S/ 80.00      │  Pasadizo: S/ 30.00        │   │
│  │    Seguridad: S/ 40.00 │  TOTAL: S/ 1700.00        │   │
│  │                                                       │   │
│  │ 🛡️  Garantía:                                       │   │
│  │    Monto: S/ 1500.00 | Recibida: 2026-06-01        │   │
│  │    Estado: Registrada  [Resolver Garantía]         │   │
│  │                                                       │   │
│  │ 📄 Documentos:                                      │   │
│  │    ✓ contrato-notariado.pdf (2.4 MB)              │   │
│  │    ✓ foto-pagina-1.jpg (1.1 MB)                   │   │
│  │                                                       │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                                │
│  HISTORIAL DE CONTRATOS                                      │
│  ├─ 2025-045: 2025-06-01 → 2026-05-31 (Vencido)            │
│  │             Inquilino: Juan Pérez | Monto: S/ 1400.00    │
│  │                                                           │
│  └─ 2024-037: 2024-09-01 → 2025-05-31 (Vencido)            │
│              Inquilino: María García | Monto: S/ 1300.00    │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

### Vista de Recibos (SPEC 005-007)

```
┌──────────────────────────────────────────────────────────────┐
│  💰 Recibos - Local A                                        │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  AGOSTO 2026                                                 │
│  Estado: [🟡 Pendiente ▼]  [✓ Pagar] [❌ Anular]           │
│  [💬 WhatsApp] [🖨️  Imprimir]                                │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Concepto              │  Cantidad  │  Monto        │   │
│  │───────────────────────┼────────────┼───────────────│   │
│  │ Alquiler              │ 1 mes      │ S/ 1500.00    │   │
│  │ Luz (150 unid.)       │ 150 kWh    │ S/ 75.00      │   │
│  │ Agua                  │ 1 mes      │ S/ 50.00      │   │
│  │ Pasadizo              │ 1 mes      │ S/ 30.00      │   │
│  │ Seguridad             │ 1 mes      │ S/ 40.00      │   │
│  │───────────────────────┼────────────┼───────────────│   │
│  │ TOTAL                 │            │ S/ 1695.00    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                                │
│  HISTORIAL DE RECIBOS                                        │
│  ┌─────────┬────────┬────────────────────────────────────┐  │
│  │ Período │ Estado │ Monto          │ Acciones         │  │
│  ├─────────┼────────┼────────────────────────────────────┤  │
│  │ Ago 26  │ 🟡 Pend│ S/ 1695.00     │ [Pagar] [Ver]    │  │
│  │ Jul 26  │ 🟢 Pag │ S/ 1685.00     │ [Imprimir]      │  │
│  │ Jun 26  │ 🟢 Pag │ S/ 1680.00     │ [Imprimir]      │  │
│  │ May 26  │ ❌ Anu │ S/ 1670.00     │ [Ver]            │  │
│  └─────────┴────────┴────────────────────────────────────┘  │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

---

## 🚀 ROADMAP DETALLADO (Timeline Estimado)

### **FASE 1: CORE BUSINESS (Semanas 1-4)**
```
Objetivo: Poder registrar y gestionar contratos básicos

📅 Semana 1: Backend + SPEC 001
├─ Modelo Locacion con relación reflexiva (parent_id)
├─ Validación de ciclos
├─ Factory y Seeder
└─ API REST: GET, POST, PUT (sin UI aún)

📅 Semana 2: UI SPEC 001 + Backend SPEC 002
├─ Vistas de Locaciones (Bootstrap)
│  ├─ Dashboard/Árbol
│  ├─ CRUD formularios
│  └─ Tabla responsive
├─ Modelo Contrato + Validación de solapamiento
├─ Modelo Inquilino
└─ API REST para Contratos

📅 Semana 3: UI SPEC 002-003-004
├─ Formulario de Contratos
│  ├─ Selección de Locación (dropdown)
│  ├─ Selección de Inquilino (search)
│  ├─ Rango de fechas (date picker)
│  ├─ Panel de Representantes
│  ├─ Sección de Costos
│  └─ Sección de Garantía
├─ Carga de documentos (Drag & drop)
├─ Búsqueda de Representantes en directorio
└─ Validación en tiempo real

📅 Semana 4: Testing + Refinement
├─ Pruebas funcionales (Solapamiento, validaciones)
├─ Pruebas de accesibilidad (WCAG AA)
├─ Optimización de performance
└─ Bug fixes y refinamiento UI

✅ RESULTADO: MVP Funcional (Locaciones + Contratos)
```

### **FASE 2: FACTURACIÓN (Semanas 5-7)**
```
Objetivo: Generar, editar y enviar recibos

📅 Semana 5: Backend SPEC 005 + Inicio UI
├─ Modelo LecturaMediador
├─ Generador de Recibos (lógica de precarga)
├─ Tabla de periodos (UI)
├─ Formulario de nueva lectura
└─ Cálculo automático de consumo

📅 Semana 6: UI SPEC 005-007
├─ Generador de Recibos
│  ├─ Modal con checkboxes de conceptos
│  ├─ Campos editables
│  ├─ Validación cruzada
│  └─ Preview antes de emitir
├─ Selector de estado (Pendiente/Pagado/Anulado)
├─ Integración con WhatsApp (generación de imagen)
└─ Vista de impresión (CSS print)

📅 Semana 7: Testing + Envíos Reales
├─ Pruebas de generación de recibos
├─ Pruebas de envío WhatsApp
├─ Pruebas de impresión
└─ Integración con email (Admin Config)

✅ RESULTADO: Sistema de Recibos Funcional
```

### **FASE 3: INTELIGENCIA (Semanas 8-10)**
```
Objetivo: Historial, reportes y automatizaciones

📅 Semana 8: Backend SPEC 006-008-009
├─ Modelo de Garantía (resolución)
├─ Alertas de vencimiento (Jobs/Scheduler)
├─ Cálculos de prorrateo
├─ Gráficos de consumo histórico
└─ Panel de Alertas

📅 Semana 9: UI SPEC 006-008-009
├─ Historial de lecturas (Tabla + Gráfico)
├─ Centro de Alertas
├─ Panel de configuración
├─ Sección de Garantía (modal de resolución)
└─ Sugerencia de prorrateo en recibos

📅 Semana 10: Testing + Automatizaciones
├─ Pruebas de jobs periódicos
├─ Pruebas de cálculos de prorrateo
├─ Pruebas de alertas por email
└─ Optimización y refinamiento

✅ RESULTADO: Sistema Completo Funcional
```

---

## 🎨 TECNOLOGÍAS POR LAYER

### **Frontend (Bootstrap 5 + Vite)**
```
┌─────────────────────────────────────┐
│ Laravel Blade Templates (Vistas)    │
├─────────────────────────────────────┤
│ Bootstrap 5.3.3 (Grid, Cards, Forms)│
├─────────────────────────────────────┤
│ Bootstrap Icons v1.11 (Iconografía) │
├─────────────────────────────────────┤
│ Alpine.js (Interactividad ligera)   │
├─────────────────────────────────────┤
│ Chart.js (Gráficos)                 │
├─────────────────────────────────────┤
│ Vite 8.x (Build tool)               │
└─────────────────────────────────────┘
```

### **Backend (Laravel 13 + PostgreSQL)**
```
┌─────────────────────────────────────┐
│ Laravel 13.x (Framework)            │
├─────────────────────────────────────┤
│ PHP 8.4 (Lenguaje)                  │
├─────────────────────────────────────┤
│ PostgreSQL 15 (Base de Datos)       │
├─────────────────────────────────────┤
│ Eloquent ORM (Modelos)              │
├─────────────────────────────────────┤
│ Laravel Queues (Jobs/Scheduler)     │
├─────────────────────────────────────┤
│ Laravel Mail (Notificaciones)       │
└─────────────────────────────────────┘
```

---

## ✅ CRITERIOS DE ACEPTACIÓN (MVP)

### Fase 1: Locaciones y Contratos
- [ ] Crear jerarquía de locaciones sin ciclos
- [ ] Visualizar breadcrumbs truncados (máx 3 niveles)
- [ ] Crear contratos sin solapamiento
- [ ] Validar rechazo de contrato duplicado con modal
- [ ] Registrar múltiples representantes con uno "principal"
- [ ] Guardar costos de referencia
- [ ] Cargar PDF o 10 fotos de contrato

### Fase 2: Recibos y Estados
- [ ] Registrar lecturas de medidor por período
- [ ] Generar recibos con concepto seleccionables
- [ ] Cambiar estado (Pendiente → Pagado → Anulado)
- [ ] Generar imagen para WhatsApp
- [ ] Imprimir recibo con CSS
- [ ] Guardar garantía y resolución

### Fase 3: Automatizaciones
- [ ] Enviar notificación de vencimiento de contrato (30, 15, 7 días)
- [ ] Alerta de fecha límite de pago (configurable)
- [ ] Calcular prorrateo automático para contratos parciales
- [ ] Gráfico de consumo histórico
- [ ] Traslado automático de lectura anterior

---

## 📞 SOPORTE TÉCNICO

### Instalación Local (Desarrollo)

```bash
# 1. Clonar y entrar
git clone <repo>
cd rent-tracker

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos
php artisan migrate --seed

# 5. Iniciar servidor
php artisan serve &  # Terminal 1
npm run dev          # Terminal 2
```

### URLs de Acceso

```
🌐 Aplicación: http://localhost:8000
👤 Login: admin@renttracker.local / password
📊 Dashboard: http://localhost:8000/dashboard
📍 Locaciones: http://localhost:8000/locaciones
📋 Contratos: http://localhost:8000/contratos
💰 Recibos: http://localhost:8000/recibos
```

---

## 🎓 REFERENCIAS RECOMENDADAS

- **Bootstrap 5 Docs**: https://getbootstrap.com/docs/5.3/
- **Bootstrap Icons**: https://icons.getbootstrap.com/
- **Laravel 13 Docs**: https://laravel.com/docs/13
- **Alpine.js**: https://alpinejs.dev/
- **Chart.js**: https://www.chartjs.org/
- **WCAG 2.1 Accessibility**: https://www.w3.org/WAI/WCAG21/quickref/

---

**Documento de referencia rápida para decisiones de UI/UX con Bootstrap 5**  
*Generado el 2026-08-19*
