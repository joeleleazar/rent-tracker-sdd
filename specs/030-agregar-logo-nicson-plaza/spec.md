# Feature Specification: Incorporar el Logo de Nicson Plaza a la Interfaz

**Feature Branch**: `030-agregar-logo-nicson-plaza`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Agregar este logo c:\Users\joel5\Downloads\Gemini_Generated_Image_dfdhedfdhedfdhed.jpg a las vistas que consideres necesario"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reconocer la marca al iniciar sesión (Priority: P1)

Un usuario que abre la pantalla de inicio de sesión ve el logo real de Nicson Plaza en vez del ícono
genérico de plantilla que muestra hoy, confirmando de inmediato que está entrando al sistema correcto.

**Why this priority**: Es la primera pantalla que ve cualquier usuario y hoy exhibe un ícono de plantilla
sin relación con el negocio — la impresión de marca más visible y sencilla de corregir.

**Independent Test**: Abrir la pantalla de inicio de sesión (sin autenticar) y verificar que el logo de
Nicson Plaza se muestra donde hoy aparece el ícono genérico.

**Acceptance Scenarios**:

1. **Given** un usuario no autenticado, **When** abre la pantalla de inicio de sesión, **Then** ve el logo
   de Nicson Plaza, con buen contraste y sin deformarse, en el lugar donde hoy se muestra el ícono
   genérico.

---

### User Story 2 - Ver la marca en toda pantalla de trabajo (Priority: P1)

Un usuario autenticado, en cualquier pantalla del sistema, ve el logo de Nicson Plaza en el encabezado de
la barra de navegación — el mismo lugar donde hoy solo aparece el texto "Rent Tracker".

**Why this priority**: El encabezado de navegación es compartido por absolutamente todas las pantallas
autenticadas — colocar el logo ahí una sola vez lo hace visible en el 100% del sistema sin tocar cada
pantalla individualmente.

**Independent Test**: Iniciar sesión y navegar a cualquier pantalla (Locaciones, Emitir Recibos,
Configuración, etc.) verificando que el logo aparece siempre en el mismo lugar del encabezado.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado, **When** visita cualquier pantalla del sistema, **Then** ve el logo de
   Nicson Plaza en el encabezado de la barra de navegación, y ese encabezado sigue enlazando al inicio.
2. **Given** una ventana angosta (donde la barra de navegación se reordena a una franja horizontal), **When**
   el usuario la visita, **Then** el logo se sigue viendo correctamente, sin romper el resto de los
   elementos de navegación ni provocar desplazamiento horizontal de la página.

---

### User Story 3 - Ver la marca en los recibos que salen del sistema (Priority: P2)

Un administrador que imprime o comparte por WhatsApp el comprobante de un recibo entrega un documento con
el logo de Nicson Plaza, no un documento genérico sin identificar.

**Why this priority**: El comprobante es el único documento de este sistema que efectivamente sale de la
aplicación hacia terceros (inquilinos) — es donde la marca importa más allá de la propia interfaz de
trabajo interna. Se prioriza después de las Historias 1 y 2 porque es una vista adicional, no compartida
por el resto del sistema.

**Independent Test**: Abrir el comprobante de un recibo (imprimible y compartible) y verificar que el logo
aparece en su encabezado, incluida la captura de imagen usada para compartir por WhatsApp.

**Acceptance Scenarios**:

1. **Given** un recibo ya emitido, **When** un administrador abre su comprobante, **Then** ve el logo de
   Nicson Plaza en el encabezado del documento.
2. **Given** ese mismo comprobante, **When** se imprime o se captura como imagen para compartir, **Then**
   el logo se incluye correctamente en ambos casos, sin romper la captura ni superponerse con la marca de
   "Anulado" cuando el recibo está anulado.

---

### User Story 4 - Reconocer la pestaña del navegador (Priority: P3)

Un usuario con varias pestañas abiertas identifica la del sistema por su ícono de marca, en vez del ícono
genérico actual.

**Why this priority**: Mejora de reconocimiento menor, independiente del resto — se prioriza al final por
ser la de menor impacto en el uso diario del sistema.

**Independent Test**: Abrir cualquier pantalla del sistema y verificar que la pestaña del navegador muestra
el ícono de Nicson Plaza.

**Acceptance Scenarios**:

1. **Given** cualquier pantalla del sistema abierta en el navegador, **When** el usuario mira la pestaña,
   **Then** ve el ícono de Nicson Plaza en vez del ícono genérico por defecto.

---

### Edge Cases

- El logo provisto es cuadrado y con márgenes generosos alrededor de la marca — en cada lugar donde se use
  debe verse proporcionado y legible, ni estirado ni recortado de forma que corte la marca.
- En el encabezado de navegación, el logo debe convivir con el resto de los elementos (enlaces de menú,
  nombre del usuario, botón de cerrar sesión) sin obligarlos a reducirse de forma ilegible.
- El comprobante de recibo se genera también como imagen capturada por el navegador (para compartir) — el
  logo debe ser compatible con ese mecanismo de captura, igual que el resto del documento ya lo es hoy.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE mostrar el logo de Nicson Plaza en la pantalla de inicio de sesión, en el
  lugar donde hoy se muestra el ícono genérico de plantilla.
- **FR-002**: El sistema DEBE mostrar el logo de Nicson Plaza en el encabezado de la barra de navegación,
  visible en toda pantalla autenticada, sin necesidad de agregarlo pantalla por pantalla.
- **FR-003**: El encabezado de navegación con el logo DEBE seguir funcionando como enlace al inicio del
  sistema, igual que hoy.
- **FR-004**: El sistema DEBE incluir el logo de Nicson Plaza en el encabezado del comprobante de recibo,
  tanto en su versión para imprimir como en la versión capturada como imagen para compartir.
- **FR-005**: El logo en el comprobante NO DEBE superponerse ni interferir con la marca visual de "Anulado"
  cuando el recibo tiene ese estado.
- **FR-006**: El sistema DEBE actualizar el ícono de pestaña del navegador (favicon) para reflejar el logo
  de Nicson Plaza.
- **FR-007**: El logo DEBE mostrarse con la misma proporción en todos los lugares donde aparece (sin
  deformarse), con suficiente contraste contra el fondo donde se coloque en cada caso.
- **FR-008**: El sistema DEBE usar el mismo archivo de logo en todos los lugares de esta especificación —
  sin variantes de marca distintas entre pantallas.

### Key Entities

*(No aplica — no se introducen ni modifican entidades de datos.)*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las pantallas autenticadas del sistema muestra el logo de Nicson Plaza en su
  encabezado de navegación, verificable visitando cualquier pantalla sin necesidad de agregarlo de nuevo.
- **SC-002**: La pantalla de inicio de sesión muestra el logo de Nicson Plaza en vez del ícono genérico
  anterior.
- **SC-003**: El 100% de los comprobantes de recibo (impresos y compartidos como imagen) incluyen el logo.
- **SC-004**: La pestaña del navegador muestra el ícono de Nicson Plaza en cualquier pantalla del sistema.

## Assumptions

- El pedido delega explícitamente en el criterio de esta especificación cuáles vistas necesitan el logo
  ("a las vistas que consideres necesario"). Se prioriza colocarlo en los dos puntos compartidos por toda la
  aplicación (encabezado de navegación e inicio de sesión), más el único documento que sale del sistema
  hacia terceros (comprobante de recibo) y el ícono de pestaña — en vez de agregarlo vista por vista, lo
  que sería redundante dado que las tres primeras ya cubren, por herencia de layout, el 100% de las
  pantallas existentes y futuras.
- En el encabezado de navegación, el logo reemplaza al texto "Rent Tracker" actual (no se muestran ambos a
  la vez) — el propio logo ya incluye el nombre "Nicson Plaza" como parte de la imagen, así que mostrar
  además el texto "Rent Tracker" junto a él generaría dos nombres de marca distintos en el mismo lugar. El
  texto del `<title>` de la pestaña del navegador y el nombre interno de la aplicación no cambian — esta
  especificación trata únicamente la marca visual (logo e ícono), no el texto de la aplicación.
- El archivo de imagen entregado por el usuario se incorpora tal cual al proyecto (sin pedir un rediseño ni
  una versión vectorial); su preparación técnica para cada uso (recortes, tamaños, formato de ícono de
  pestaña) queda para la fase de planificación.
