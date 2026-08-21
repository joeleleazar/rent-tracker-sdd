# Guía Práctica: Componentes Bootstrap 5 para Rent Tracker

**Última actualización**: 2026-08-19

---

## 📌 Resumen Rápido: Qué specs necesitas para cada vista

```
VISTAS CRÍTICAS (P0) - Specs 001-004
├── Vista de Locaciones → spec 001
├── Formulario de Contrato → specs 002, 003, 004
├── Panel de Representantes → spec 003
└── Sección de Costos → spec 004

VISTAS IMPORTANTE (P1) - Specs 005, 007, 009
├── Generador de Recibos → spec 005
├── Estado de Recibos → spec 007
└── Garantía de Contrato → spec 009

VISTAS COMPLEMENTARIA (P2) - Specs 006, 008
├── Historial de Consumo → spec 006
└── Centro de Alertas → spec 008
```

---

## 🔧 Componentes Recomendados por Spec

### **SPEC 001 - JERARQUÍA DE LOCACIONES**

#### Componente 1: Breadcrumbs Accesible
```html
<!-- En Laravel Blade (resources/views/locaciones/show.blade.php) -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb fs-5 fw-bold">
    <!-- Mostrar max 3 niveles -->
    <li class="breadcrumb-item">
      <a href="/locaciones/1" class="text-decoration-none text-primary">Galería El Sol</a>
    </li>
    <li class="breadcrumb-item">
      <a href="/locaciones/2" class="text-decoration-none text-primary">Piso 1</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page" style="font-size: 20px;">
      Local A (Alquilable)
    </li>
  </ol>
</nav>
```

#### Componente 2: Tarjeta de Locación
```html
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h2 class="card-title fs-4 fw-bold mb-3">Local A</h2>
    
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Tamaño</label>
        <input type="text" class="form-control form-control-lg" 
               value="120.5 m²" readonly>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Ubicación</label>
        <input type="text" class="form-control form-control-lg" 
               value="Sector Norte" readonly>
      </div>
    </div>
    
    <div class="mt-3">
      <label class="form-label fw-bold fs-5">Descripción</label>
      <textarea class="form-control form-control-lg" rows="3" readonly>
        Primer nivel de la galería con fachada al pasaje principal
      </textarea>
    </div>
    
    <div class="mt-3">
      <span class="badge bg-success fs-5 p-3">
        ✓ Alquilable
      </span>
    </div>
  </div>
  
  <div class="card-footer bg-light d-flex gap-2">
    <button class="btn btn-primary btn-lg" style="min-width: 48px; min-height: 48px;">
      ✎ Editar
    </button>
    <button class="btn btn-secondary btn-lg" style="min-width: 48px; min-height: 48px;">
      📋 Ver Contratos
    </button>
  </div>
</div>
```

#### Componente 3: Tabla de Locaciones Jerárquicas
```html
<div class="table-responsive">
  <table class="table table-hover table-lg">
    <thead class="table-light">
      <tr>
        <th style="font-size: 18px;">Locación</th>
        <th style="font-size: 18px;">Tamaño</th>
        <th style="font-size: 18px;">Tipo</th>
        <th style="font-size: 18px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="fw-bold">Galería El Sol</td>
        <td>500 m²</td>
        <td><span class="badge bg-secondary">Contenedor</span></td>
        <td>
          <button class="btn btn-sm btn-primary">Editar</button>
        </td>
      </tr>
      <tr class="table-active">
        <td style="padding-left: 40px;">└─ Piso 1</td>
        <td>250 m²</td>
        <td><span class="badge bg-secondary">Contenedor</span></td>
        <td>
          <button class="btn btn-sm btn-primary">Editar</button>
        </td>
      </tr>
      <tr class="table-active">
        <td style="padding-left: 80px;">└─ Local A</td>
        <td>120.5 m²</td>
        <td><span class="badge bg-success">Alquilable</span></td>
        <td>
          <button class="btn btn-sm btn-primary">Editar</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

---

### **SPEC 002 - GESTIÓN DE CONTRATOS**

#### Componente 1: Área de Carga de Documentos
```html
<div class="card border-2 border-primary border-dashed p-4 text-center" 
     style="background-color: #f0f7ff;">
  
  <h3 class="fs-4 fw-bold mb-3">Seleccionar Documento</h3>
  
  <div class="btn-group-vertical d-flex gap-3 w-100" role="group">
    <!-- Opción PDF -->
    <label class="btn btn-outline-primary btn-lg" style="min-height: 60px;">
      <input type="file" accept=".pdf" class="d-none" name="contract_pdf">
      <span style="font-size: 18px;">
        📄 Seleccionar PDF del Contrato (máx 15MB)
      </span>
    </label>
    
    <!-- Opción Fotos -->
    <label class="btn btn-outline-primary btn-lg" style="min-height: 60px;">
      <input type="file" accept=".jpg,.jpeg,.png" multiple class="d-none" 
             name="contract_photos" data-max-files="10">
      <span style="font-size: 18px;">
        📸 Subir Fotos de Páginas (máx 10, 5MB c/u)
      </span>
    </label>
  </div>
  
  <p class="text-muted fs-6 mt-4">
    O arrastra archivos aquí
  </p>
</div>

<!-- Vista previa de documentos cargados -->
<div class="mt-4">
  <h4 class="fs-5 fw-bold">Documentos Cargados:</h4>
  
  <div class="list-group">
    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
      <div>
        <h5 class="mb-1 fs-5">📄 contrato-firmado.pdf</h5>
        <small class="text-muted">2.4 MB • Subido hace 2 min</small>
      </div>
      <button class="btn btn-danger btn-sm" style="min-height: 44px; min-width: 44px;">
        🗑️
      </button>
    </div>
  </div>
</div>
```

#### Componente 2: Modal de Validación de Solapamiento
```html
<!-- Modal que aparece al intentar guardar contrato conflictivo -->
<div class="modal fade" id="overlapWarningModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-3 border-danger">
      
      <div class="modal-header bg-danger text-white p-4">
        <h2 class="modal-title fs-3 fw-bold">
          ⚠️ Conflicto de Fechas Detectado
        </h2>
      </div>
      
      <div class="modal-body p-4" style="font-size: 18px;">
        <p class="fw-bold text-danger mb-3">
          La locación "Local A" ya tiene un contrato activo en este período:
        </p>
        
        <div class="alert alert-warning p-3" role="alert">
          <strong>Contrato Existente:</strong><br>
          📅 Desde: 2026-06-01 | Hasta: 2026-12-31<br>
          👤 Inquilino: Carlos Rodríguez<br>
          💰 Monto: S/ 1500.00
        </div>
        
        <p class="fw-bold text-danger mb-3">
          Su nuevo contrato se superpone:
        </p>
        
        <div class="alert alert-warning p-3" role="alert">
          <strong>Nuevo Contrato:</strong><br>
          📅 Desde: 2026-09-01 | Hasta: 2027-08-31<br>
          👤 Inquilino: María García<br>
          💰 Monto: S/ 1600.00
        </div>
        
        <p class="mt-3">
          Debe <strong>rescindir o modificar el contrato existente</strong> 
          antes de registrar este nuevo contrato.
        </p>
      </div>
      
      <div class="modal-footer p-4 gap-2">
        <button type="button" class="btn btn-secondary btn-lg" 
                data-bs-dismiss="modal" style="min-width: 150px;">
          Cancelar
        </button>
        <a href="/contratos/1/edit" class="btn btn-warning btn-lg" 
           style="min-width: 200px;">
          Editar Contrato Existente
        </a>
      </div>
      
    </div>
  </div>
</div>
```

#### Componente 3: Timeline de Contratos Históricos
```html
<div class="timeline">
  <div class="timeline-item mb-5 pb-4 border-bottom">
    <div class="row">
      <div class="col-auto">
        <!-- Indicador de fecha -->
        <div class="position-relative" style="left: -12px;">
          <span class="badge bg-success p-3" style="font-size: 14px;">
            ✓ ACTIVO
          </span>
        </div>
      </div>
      <div class="col">
        <h5 class="fs-5 fw-bold">Contrato 2026-006</h5>
        <p class="text-muted mb-2">📅 2026-06-01 hasta 2026-12-31</p>
        <p class="mb-2"><strong>Inquilino:</strong> Carlos Rodríguez Pérez</p>
        <p class="mb-2"><strong>Monto:</strong> S/ 1500.00/mes</p>
        <button class="btn btn-sm btn-primary">Ver Detalles</button>
        <button class="btn btn-sm btn-warning">Rescindir</button>
      </div>
    </div>
  </div>
  
  <div class="timeline-item mb-5 pb-4 border-bottom">
    <div class="row">
      <div class="col-auto">
        <div class="position-relative" style="left: -12px;">
          <span class="badge bg-secondary p-3" style="font-size: 14px;">
            VENCIDO
          </span>
        </div>
      </div>
      <div class="col">
        <h5 class="fs-5 fw-bold">Contrato 2025-045</h5>
        <p class="text-muted mb-2">📅 2025-06-01 hasta 2026-05-31</p>
        <p class="mb-2"><strong>Inquilino:</strong> Juan Pérez García</p>
        <p class="mb-2"><strong>Monto:</strong> S/ 1400.00/mes</p>
        <button class="btn btn-sm btn-primary">Ver Detalles</button>
      </div>
    </div>
  </div>
</div>
```

---

### **SPEC 003 - REPRESENTANTES DE CONTRATO**

#### Componente 1: Panel de Representantes
```html
<div class="card border-primary">
  <div class="card-header bg-primary text-white p-4">
    <h3 class="fs-4 fw-bold mb-0">
      👥 Representantes del Contrato (Mínimo 1 requerido)
    </h3>
  </div>
  
  <div class="card-body p-4">
    
    <!-- Lista de representantes -->
    <div class="row g-3 mb-4">
      
      <!-- Tarjeta de Representante 1 -->
      <div class="col-md-6">
        <div class="card p-3 border-2" style="border-color: #0D6EFD;">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h5 class="fs-5 fw-bold mb-1">Juan Carlos Pérez Gómez</h5>
              <p class="text-muted mb-2">DNI: 12345678</p>
              <p class="text-muted">📅 Nac: 15/05/1960</p>
            </div>
            
            <!-- Badge Principal -->
            <span class="badge bg-success p-2" style="font-size: 14px;">
              ★ PRINCIPAL
            </span>
          </div>
          
          <div class="btn-group w-100 gap-2" role="group">
            <button class="btn btn-outline-warning btn-sm fs-6" 
                    style="min-height: 44px;">
              ✓ Principal
            </button>
            <button class="btn btn-outline-danger btn-sm fs-6" 
                    style="min-height: 44px;" 
                    data-bs-toggle="modal" 
                    data-bs-target="#removeRepresentativeModal">
              🗑️ Remover
            </button>
          </div>
        </div>
      </div>
      
      <!-- Tarjeta de Representante 2 -->
      <div class="col-md-6">
        <div class="card p-3">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h5 class="fs-5 fw-bold mb-1">María Rosa López Martínez</h5>
              <p class="text-muted mb-2">DNI: 87654321</p>
              <p class="text-muted">📅 Nac: 22/11/1965</p>
            </div>
          </div>
          
          <div class="btn-group w-100 gap-2" role="group">
            <button class="btn btn-outline-success btn-sm fs-6" 
                    style="min-height: 44px;">
              ☆ Marcar Principal
            </button>
            <button class="btn btn-outline-danger btn-sm fs-6" 
                    style="min-height: 44px;">
              🗑️ Remover
            </button>
          </div>
        </div>
      </div>
      
    </div>
    
    <!-- Botón para agregar más representantes -->
    <button class="btn btn-success btn-lg w-100" style="min-height: 56px; font-size: 18px;">
      + Agregar Otro Representante
    </button>
  </div>
</div>

<!-- Modal de búsqueda en directorio -->
<div class="modal fade" id="searchRepresentativeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      <div class="modal-header p-4">
        <h2 class="modal-title fs-4 fw-bold">
          Buscar Representante en Directorio
        </h2>
      </div>
      
      <div class="modal-body p-4">
        <input type="text" class="form-control form-control-lg mb-3" 
               placeholder="Ingrese DNI o Apellidos"
               style="font-size: 18px;">
        
        <!-- Resultados -->
        <div class="list-group">
          <button type="button" class="list-group-item list-group-item-action p-3 text-start"
                  onclick="selectRepresentative(this)">
            <h5 class="fs-5 fw-bold mb-1">Juan Carlos Pérez Gómez</h5>
            <p class="text-muted mb-0">DNI: 12345678 | Nac: 15/05/1960</p>
          </button>
          
          <button type="button" class="list-group-item list-group-item-action p-3 text-start"
                  onclick="selectRepresentative(this)">
            <h5 class="fs-5 fw-bold mb-1">Carlos Rodríguez López</h5>
            <p class="text-muted mb-0">DNI: 11223344 | Nac: 08/03/1955</p>
          </button>
        </div>
      </div>
      
      <div class="modal-footer p-4 gap-2">
        <button type="button" class="btn btn-secondary btn-lg" 
                data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="button" class="btn btn-primary btn-lg">
          Agregar Seleccionado
        </button>
      </div>
      
    </div>
  </div>
</div>

<!-- Modal de confirmación para remover -->
<div class="modal fade" id="removeRepresentativeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-3 border-danger">
      
      <div class="modal-header bg-danger text-white p-4">
        <h2 class="modal-title fs-3 fw-bold">
          ⚠️ Confirmar Eliminación
        </h2>
      </div>
      
      <div class="modal-body p-4" style="font-size: 18px;">
        <p class="fw-bold mb-3">
          ¿Está seguro que desea remover este representante?
        </p>
        <div class="alert alert-warning p-3">
          <strong>Juan Carlos Pérez Gómez</strong><br>
          DNI: 12345678
        </div>
        <p class="text-danger fw-bold">
          Esta acción no se puede deshacer.
        </p>
      </div>
      
      <div class="modal-footer p-4 gap-2">
        <button type="button" class="btn btn-secondary btn-lg" 
                data-bs-dismiss="modal" style="min-width: 150px;">
          No, Cancelar
        </button>
        <button type="button" class="btn btn-danger btn-lg" style="min-width: 150px;">
          Sí, Remover Representante
        </button>
      </div>
      
    </div>
  </div>
</div>
```

---

### **SPEC 004 - CONDICIONES DE CONTRATO**

#### Componente 1: Sección de Costos
```html
<div class="card border-success">
  <div class="card-header bg-success text-white p-4">
    <h3 class="fs-4 fw-bold mb-0">💰 Costos de Referencia del Contrato</h3>
  </div>
  
  <div class="card-body p-4">
    
    <div class="row g-4">
      
      <!-- Costo de Renta -->
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Costo de Renta Mensual</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-light fw-bold" style="font-size: 18px;">S/</span>
          <input type="number" class="form-control" 
                 step="0.01" min="0" value="1500.00"
                 placeholder="0.00" 
                 style="font-size: 18px;">
        </div>
        <small class="text-muted d-block mt-2">
          Monto base de alquiler mensual
        </small>
      </div>
      
      <!-- Costo de Agua -->
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Costo de Agua</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-light fw-bold" style="font-size: 18px;">S/</span>
          <input type="number" class="form-control" 
                 step="0.01" min="0" value="50.00"
                 placeholder="0.00" 
                 style="font-size: 18px;">
        </div>
      </div>
      
      <!-- Costo de Luz -->
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Costo de Luz (Base)</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-light fw-bold" style="font-size: 18px;">S/</span>
          <input type="number" class="form-control" 
                 step="0.01" min="0" value="80.00"
                 placeholder="0.00" 
                 style="font-size: 18px;">
        </div>
        <small class="text-muted d-block mt-2">
          Se completará con lectura de medidor
        </small>
      </div>
      
      <!-- Costo de Pasadizo -->
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Costo de Pasadizo/Mantenimiento</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-light fw-bold" style="font-size: 18px;">S/</span>
          <input type="number" class="form-control" 
                 step="0.01" min="0" value="30.00"
                 placeholder="0.00" 
                 style="font-size: 18px;">
        </div>
      </div>
      
      <!-- Costo de Seguridad -->
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Costo de Seguridad</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-light fw-bold" style="font-size: 18px;">S/</span>
          <input type="number" class="form-control" 
                 step="0.01" min="0" value="40.00"
                 placeholder="0.00" 
                 style="font-size: 18px;">
        </div>
      </div>
      
      <!-- Total de Referencia (solo lectura) -->
      <div class="col-md-6">
        <label class="form-label fw-bold fs-5">Total de Referencia</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-light fw-bold" style="font-size: 18px;">S/</span>
          <input type="text" class="form-control bg-light fw-bold" 
                 value="1700.00" readonly
                 style="font-size: 18px; color: #0D6EFD;">
        </div>
        <small class="text-muted d-block mt-2">
          Suma de todos los costos
        </small>
      </div>
      
    </div>
    
    <!-- Advertencia de que estos son valores de referencia -->
    <div class="alert alert-info mt-4 p-3" role="alert" style="font-size: 16px;">
      <strong>ℹ️ Nota:</strong> Estos son valores de <strong>referencia</strong>. 
      Al generar un recibo, estos montos se sugieren pero pueden ser editados 
      según el consumo real o ajustes puntuales.
    </div>
    
  </div>
</div>
```

---

### **SPEC 005 - LECTURAS DE MEDIDOR Y RECIBOS**

#### Componente 1: Tabla de Periodos con Lecturas
```html
<div class="card">
  <div class="card-header bg-light p-4 d-flex justify-content-between align-items-center">
    <h3 class="fs-4 fw-bold mb-0">⚡ Lecturas de Medidor - Local A</h3>
    <button class="btn btn-primary btn-lg" style="min-height: 48px;">
      + Registrar Nueva Lectura
    </button>
  </div>
  
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th style="font-size: 18px; font-weight: bold;">Período</th>
          <th style="font-size: 18px; font-weight: bold;">Lectura Anterior</th>
          <th style="font-size: 18px; font-weight: bold;">Lectura Actual</th>
          <th style="font-size: 18px; font-weight: bold;">Consumo</th>
          <th style="font-size: 18px; font-weight: bold;">Estado</th>
          <th style="font-size: 18px; font-weight: bold;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr class="table-active">
          <td class="fw-bold fs-5">Agosto 2026</td>
          <td>1250</td>
          <td>1400</td>
          <td class="fw-bold text-success fs-5">150 unidades</td>
          <td>
            <span class="badge bg-success p-2" style="font-size: 14px;">
              ✓ Registrada
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-warning">Editar</button>
            <button class="btn btn-sm btn-info">Generar Recibo</button>
          </td>
        </tr>
        
        <tr>
          <td class="fw-bold fs-5">Julio 2026</td>
          <td>1120</td>
          <td>1250</td>
          <td class="fw-bold text-success fs-5">130 unidades</td>
          <td>
            <span class="badge bg-success p-2" style="font-size: 14px;">
              ✓ Registrada
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-warning">Editar</button>
            <button class="btn btn-sm btn-secondary">Ver Recibo</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Formulario de Nueva Lectura (Modal) -->
<div class="modal fade" id="newReadingModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      <div class="modal-header p-4">
        <h2 class="modal-title fs-4 fw-bold">
          ⚡ Registrar Nueva Lectura de Medidor
        </h2>
      </div>
      
      <div class="modal-body p-4">
        
        <!-- Período -->
        <div class="mb-4">
          <label class="form-label fw-bold fs-5">Período (Mes y Año)</label>
          <input type="month" class="form-control form-control-lg" 
                 style="font-size: 18px;">
        </div>
        
        <!-- Lectura Anterior (informativa y editable) -->
        <div class="mb-4">
          <label class="form-label fw-bold fs-5">
            Lectura Anterior
            <span class="text-info ms-2">
              (Trasladada del período anterior - Editable)
            </span>
          </label>
          <input type="number" class="form-control form-control-lg" 
                 value="1250" placeholder="Sin lectura previa"
                 style="font-size: 18px;">
        </div>
        
        <!-- Lectura Actual -->
        <div class="mb-4">
          <label class="form-label fw-bold fs-5">Lectura Actual</label>
          <input type="number" class="form-control form-control-lg" 
                 placeholder="Ingrese la lectura del medidor"
                 style="font-size: 18px;">
        </div>
        
        <!-- Consumo Calculado (automático, solo lectura) -->
        <div class="mb-4">
          <label class="form-label fw-bold fs-5">Consumo Calculado</label>
          <input type="text" class="form-control form-control-lg bg-light" 
                 value="150 unidades" readonly
                 style="font-size: 18px; color: #198754; font-weight: bold;">
          <small class="text-muted d-block mt-2">
            Se calcula automáticamente: Lectura Actual - Lectura Anterior
          </small>
        </div>
        
        <!-- Validación de consumo negativo (alerta) -->
        <div class="alert alert-warning d-none" id="negativeConsumptionAlert">
          ⚠️ El consumo sería negativo. Verifique que la lectura actual 
          sea mayor que la lectura anterior, o que el medidor haya sido reiniciado.
        </div>
        
      </div>
      
      <div class="modal-footer p-4 gap-2">
        <button type="button" class="btn btn-secondary btn-lg" 
                data-bs-dismiss="modal" style="min-width: 150px;">
          Cancelar
        </button>
        <button type="button" class="btn btn-success btn-lg" style="min-width: 150px;">
          Guardar Lectura
        </button>
      </div>
      
    </div>
  </div>
</div>
```

---

### **SPEC 007 - ESTADO Y ENVÍO DE RECIBOS**

#### Componente 1: Gestor de Estados de Recibo
```html
<div class="card shadow-sm">
  <div class="card-body p-4">
    
    <div class="row align-items-center mb-4">
      <div class="col">
        <h3 class="fs-4 fw-bold mb-1">Recibo - Agosto 2026</h3>
        <p class="text-muted mb-0">Local A | Carlos Rodríguez</p>
      </div>
      
      <div class="col-auto">
        <!-- Selector de Estado -->
        <div class="btn-group" role="group">
          <input type="radio" class="btn-check" name="receipt-status" 
                 id="status-pending" value="pending" checked>
          <label class="btn btn-outline-warning btn-lg p-3" 
                 for="status-pending" style="font-size: 16px;">
            🟡 Pendiente
          </label>
          
          <input type="radio" class="btn-check" name="receipt-status" 
                 id="status-paid" value="paid">
          <label class="btn btn-outline-success btn-lg p-3" 
                 for="status-paid" style="font-size: 16px;">
            🟢 Pagado
          </label>
          
          <input type="radio" class="btn-check" name="receipt-status" 
                 id="status-canceled" value="canceled">
          <label class="btn btn-outline-danger btn-lg p-3" 
                 for="status-canceled" style="font-size: 16px;">
            ❌ Anulado
          </label>
        </div>
      </div>
    </div>
    
    <!-- Información de pago (solo si está pagado) -->
    <div class="alert alert-success d-none" id="paidInfo" role="alert">
      <strong>✓ Pagado</strong> • 2026-08-25
    </div>
    
    <!-- Detalle de Concepto/Monto -->
    <div class="table-responsive mb-4">
      <table class="table">
        <thead class="table-light">
          <tr>
            <th style="font-size: 18px;">Concepto</th>
            <th style="font-size: 18px; text-align: right;">Monto</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="font-size: 18px;">Alquiler</td>
            <td style="font-size: 18px; text-align: right; font-weight: bold;">S/ 1500.00</td>
          </tr>
          <tr>
            <td style="font-size: 18px;">Luz (150 unidades)</td>
            <td style="font-size: 18px; text-align: right; font-weight: bold;">S/ 75.00</td>
          </tr>
          <tr>
            <td style="font-size: 18px;">Agua</td>
            <td style="font-size: 18px; text-align: right; font-weight: bold;">S/ 50.00</td>
          </tr>
          <tr>
            <td style="font-size: 18px;">Seguridad</td>
            <td style="font-size: 18px; text-align: right; font-weight: bold;">S/ 40.00</td>
          </tr>
          <tr class="table-light">
            <td style="font-size: 20px; font-weight: bold;">TOTAL</td>
            <td style="font-size: 20px; text-align: right; font-weight: bold; color: #0D6EFD;">
              S/ 1665.00
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Botones de Acción -->
    <div class="row gap-2">
      <div class="col-md-6">
        <button class="btn btn-success btn-lg w-100" style="min-height: 56px; font-size: 18px;">
          💬 Enviar por WhatsApp
        </button>
      </div>
      <div class="col-md-6">
        <button class="btn btn-primary btn-lg w-100" style="min-height: 56px; font-size: 18px;">
          🖨️ Imprimir Recibo
        </button>
      </div>
    </div>
    
  </div>
</div>
```

#### Componente 2: Vista de Impresión
```html
<!-- En CSS o como media query -->
@media print {
  .no-print {
    display: none !important;
  }
  
  .receipt-print {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: white;
  }
  
  .receipt-print table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .receipt-print td, .receipt-print th {
    border: 1px solid #000;
    padding: 12px;
    text-align: left;
  }
  
  .receipt-print h1 {
    text-align: center;
    font-size: 28px;
  }
  
  .receipt-print .total {
    font-size: 24px;
    font-weight: bold;
    text-align: right;
  }
}
```

---

## ✅ Lista de Verificación Final

### Antes de lanzar cada spec:
- [ ] Tipografía base ≥ 18px
- [ ] Contraste WCAG AA (4.5:1 mínimo)
- [ ] Botones ≥ 48x48px
- [ ] Inputs ≥ 44px altura
- [ ] Modales con confirmación
- [ ] Validación en tiempo real
- [ ] Responsive (mobile-first)
- [ ] Accesibilidad (aria-labels, roles)

---

**Última revisión**: 2026-08-19 | **Versión**: 1.0
