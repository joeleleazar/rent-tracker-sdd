<!--
Sync Impact Report
Version change: 1.0.0 → 1.1.0
Modified principles: N/A (principios I-V sin cambios de fondo)
Added sections:
- Core Principles:
  * VI. Sistema de Componentes Visuales (Bootstrap 5)
Removed sections: N/A
Modified sections:
- Restricciones Técnicas y Estándares de Accesibilidad: la línea "Frontend / Vistas" ahora
  nombra explícitamente Bootstrap 5.3 + Bootstrap Icons como el sistema de componentes vigente
  (antes decía genéricamente "Blade Templates o componentes desacoplados").
Follow-up TODOs: Ninguno. Los documentos de referencia que originaron este principio
(ANALISIS_SPECS_PARA_VISTAS_BOOTSTRAP.md, GUIA_COMPONENTES_BOOTSTRAP.md,
RESUMEN_EJECUTIVO_SPECS.md) se trasladaron a docs/referencias-diseno-bootstrap/ como
material de consulta; no son parte normativa de esta constitución, y donde entran en
conflicto con decisiones ya implementadas (specs 010/011), este principio documenta
explícitamente cuál versión prevalece y por qué.
-->

# Constitución del Sistema de Gestión de Alquileres

## Core Principles

### I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)
El sistema DEBE desarrollarse utilizando PHP (versión 8.2 o superior) y el framework Laravel en su versión estable más reciente, empleando PostgreSQL como motor de base de datos relacional primario.
- La arquitectura DEBE respetar las convenciones idiomáticas y patrones de diseño recomendados por Laravel (MVC, Eloquent ORM, Migraciones, Seeders, Form Requests, Services/Actions para lógica de negocio desacoplada).
- El esquema de base de datos DEBE aprovechar las capacidades relacionales e integridad de PostgreSQL (claves foráneas, índices, restricciones `CHECK`, tipos de datos específicos como `NUMERIC` para montos monetarios y marcas temporales con zona horaria).
- Queda prohibido el uso de consultas SQL directas no sanitizadas o bypass del ORM sin justificación técnica y auditoría previa.

### II. Nomenclatura y Código Estrictamente en Español
Todo el código fuente del proyecto DEBE redactarse íntegramente en idioma español.
- **Entidades y Clases**: Nombres de modelos, controladores, servicios, migraciones, middleware y eventos en español (ej. `Inmueble`, `Inquilino`, `Pago`, `Contrato`, `ControladorInmueble`, `RegistrarPagoRequest`).
- **Métodos y Variables**: Nombres de funciones, métodos de acceso/mutación, variables locales y propiedades en español (ej. `$montoAlquiler`, `obtenerPagosPendientes()`, `calcularSaldoTotal()`).
- **Base de Datos**: Nombres de tablas, columnas y claves foráneas en español (ej. `inmuebles`, `inquilinos`, `pagos_mensuales`, `inmueble_id`, `fecha_vencimiento`).
- **Comentarios y Documentación**: Todos los bloques de comentarios (PHPDoc), comentarios en línea y mensajes de registro (logging) DEBEN estar escritos en español gramaticalmente correcto y descriptivo.

### III. Accesibilidad Extrema y UX para Adultos Mayores (Senior-First)
La interfaz de usuario DEBE diseñarse específicamente para garantizar la máxima facilidad de uso, legibilidad y autonomía de usuarios adultos mayores.
- **Tipografía y Legibilidad**: Tamaño tipográfico base de mínimo 18px en texto de lectura/cuerpo, y jerarquía visual clara con títulos y subtítulos proporcionales y destacados. Fuentes legibles sin serifa (sans-serif) con espaciado entre líneas y letras optimizado.
- **Alto Contraste**: El esquema cromático DEBE cumplir o exceder el ratio de contraste WCAG AAA/AA (mínimo 4.5:1 para texto normal y 3:1 para componentes interactivos grandes) evitando tonos pastel o grises tenues sobre fondos claros.
- **Componentes Táctiles e Interactivos**: Botones y áreas de clic amplias (mínimo 48x48px de área táctil/interactiva) con etiquetas textuales directas, unívocas y explícitas (ej. "Guardar Inmueble", "Registrar Pago de Este Mes", en lugar de íconos aislados o abreviaturas).
- **Navegación Simple y Plana**: La estructura de navegación DEBE ser lineal y horizontal/vertical fija, eliminando menús desplegables flotantes (dropdowns) complejos, acordeones anidados o elementos que requieran destreza motriz fina.
- **Prevención de Errores y Confirmaciones Explícitas**: Toda acción destructiva o irreversible (eliminar registros, anular pagos, cancelar contratos) DEBE requerir una pantalla o modal de confirmación con lenguaje claro, advertencia visible y botones diferenciados por color y texto descriptivo ("Sí, eliminar inmueble" vs "No, cancelar").

### IV. Pruebas Automatizadas Exhaustivas (Modelos y Controladores)
El desarrollo DEBE contar con una suite de pruebas automatizadas (PHPUnit / Pest) exhaustiva y de ejecución obligatoria.
- **Pruebas en Modelos**: Cobertura obligatoria de relaciones Eloquent, scopes de consulta, reglas de casteo/mutación, validaciones internas de modelo y métodos de cálculo de negocio.
- **Pruebas en Controladores**: Cobertura obligatoria de flujos de éxito (happy path), validación de entradas erróneas o incompletas en `FormRequest`, respuestas HTTP adecuadas (códigos 200, 201, 302, 403, 422), autorización de accesos y persistencia efectiva en base de datos.
- **Criterio de Aceptación CI**: Ninguna funcionalidad o modificación puede integrarse a la rama principal si las pruebas automatizadas fallan o no cubren los nuevos caminos lógicos introducidos.

### V. Integridad de Datos y Seguridad Transaccional
La gestión de contratos, inmuebles y transacciones económicas DEBE blindar la consistencia y exactitud financiera.
- **Transacciones Atómicas**: Cualquier operación que involucre múltiples cambios en base de datos (ej. registro de pago con actualización de saldo y emisión de comprobante) DEBE ejecutarse dentro de transacciones de base de datos (`DB::transaction`).
- **Precisión Numérica**: Todos los cálculos de alquiler, recargos o pagos parciales DEBEN utilizar tipos de datos exactos (`DECIMAL`/`NUMERIC` en PostgreSQL, `decimal:2` en casts de Laravel) prohibiendo el uso de tipos flotantes de punto flotante inexacto.
- **Seguridad**: Validación estricta en servidor en todas las peticiones, protección CSRF activa en todos los formularios web y sanitización contra inyecciones XSS y SQL.

### VI. Sistema de Componentes Visuales (Bootstrap 5)
La interfaz DEBE construirse sobre Bootstrap 5.3 (compilado desde su fuente Sass, no el CSS precompilado) y Bootstrap Icons como el sistema de componentes visuales oficial y único del proyecto, con las variables de diseño de Bootstrap personalizadas para satisfacer siempre el Principio III en vez de usarlo con su configuración por defecto.

- **Convenciones por tipo de contenido**: `card` para presentar un registro individual (locación, contrato, recibo); `table-responsive` + `table-hover` para listados tabulares; el componente `Modal` nativo de Bootstrap para toda confirmación destructiva y para formularios secundarios embebidos (nunca un `<dialog>` o modal casero); `badge` con color semántico (`bg-success`/`bg-warning`/`bg-danger`/`bg-secondary`) para representar el estado de un registro (pagado/pendiente/anulado, alquilable/no alquilable); `input-group` con el prefijo "S/" para todo campo de monto monetario; `breadcrumb` para representar jerarquías (ej. la ruta de locaciones).
- **Iconografía**: Bootstrap Icons (`bi-*`) DEBE usarse de forma consistente — el mismo ícono y color para el mismo concepto de acción o estado en toda la aplicación (ej. `bi-trash` siempre para eliminar/quitar/anular, `bi-pencil-square` siempre para editar, `bi-plus-lg` siempre para crear/agregar). Los íconos son siempre un refuerzo visual adicional a una etiqueta textual explícita (Principio III), nunca un reemplazo de esa etiqueta.
- **Checklist de cumplimiento Bootstrap 5** (verificado en cada vista nueva o modificada): tipografía base ≥18px; botones y áreas táctiles ≥48x48px; inputs con altura mínima equivalente (`form-control-lg`/`form-select-lg` o el tamaño base ya ajustado por variables); contraste ≥4.5:1 verificado con los valores reales de la paleta del proyecto (ver excepción de paleta más abajo); diseño responsive sin scroll horizontal (`container`/`row`/`col-*`); iconografía consistente (ver punto anterior); validación de formularios en tiempo real donde el navegador lo soporte nativamente (`required`, `type="email"`, etc.), sin sustituir la validación de servidor; atributos de accesibilidad (`aria-label`, `aria-hidden` en íconos decorativos, `role="alert"` en mensajes); estilos de impresión (`@media print`) en las vistas que lo requieran (ej. comprobantes de recibo).
- **Excepción de paleta de colores**: la paleta de colores vinculante del proyecto es la definida en `resources/css/bootstrap.scss` (variables `$primary`/`$secondary`/`$success`/`$danger`/`$warning`/`$info`), no la paleta genérica de cualquier documento de referencia de diseño. Estos valores fueron elegidos deliberadamente más oscuros que los por defecto de Bootstrap (ej. `$warning: #92400e` en vez de `#FFC107`) para satisfacer el contraste mínimo del Principio III; ningún documento de referencia de diseño puede introducir un color que no cumpla ese contraste, sin importar qué tan extendido sea su uso en dicho documento.
- **Excepción de interactividad asíncrona**: la interactividad de escritura (crear/editar/eliminar) del proyecto se implementa con **htmx** (`hx-boost`), no con Alpine.js, por la decisión técnica documentada en `specs/011-elevacion-diseno-async/research.md` — htmx permite que la interfaz se sienta asíncrona sin exigir cambios en los controladores y con degradación elegante a envío clásico si JavaScript falla, lo que Alpine.js no resuelve de la misma forma. Cualquier sugerencia futura de usar Alpine.js para este propósito queda descartada salvo que se documente una nueva reconciliación explícita.
- **Documentos de referencia de diseño**: material de consulta no normativo (mockups, wireframes, ejemplos de componentes) vive en `docs/referencias-diseno-bootstrap/`, fuera de este documento. Donde ese material sugiera algo que contradiga una decisión ya implementada y documentada en una spec (ej. navegación con sidebar en vez de navbar plano, definido en `specs/010-migracion-interfaz-bootstrap`), la decisión ya implementada prevalece salvo que una nueva spec la reemplace explícitamente.

## Restricciones Técnicas y Estándares de Accesibilidad

- **Entorno de Ejecución**: PHP 8.2+ | Laravel 11.x | PostgreSQL 15+.
- **Frontend / Vistas**: Bootstrap 5.3 + Bootstrap Icons (ver Principio VI) sobre Blade Templates, respetando estrictamente las pautas WCAG 2.1 (Nivel AA/AAA).
- **Diseño Responsivo y Zoom**: La interfaz DEBE permitir zoom de navegador de hasta 200% sin ruptura de diseño, pérdida de contenido o aparición de scroll horizontal no deseado.
- **Mensajes de Estado y Feedback**: Notificaciones de éxito, error o alerta presentadas con mensajes persistentes, colores de alto contraste e iconos de soporte comprensibles (ej. banner verde con texto: "El pago fue registrado exitosamente").

## Flujo de Trabajo, Calidad y Criterios de Aceptación

1. **Especificación Previa**: Toda funcionalidad DEBE definirse primero mediante especificación técnica funcional y requisitos de usuario aprobados.
2. **Ciclo de Pruebas**: Redacción de pruebas unitarias y de integración junto con la lógica de negocio; verificación obligatoria en entorno local antes de integración.
3. **Revisión de Cumplimiento de Accesibilidad**: Verificación manual y automatizada de contrastes de color, tamaños de fuente (mínimo 18px) y claridad de textos en cada vista nueva o modificada.
4. **Revisión de Nomenclatura en Español**: Auditoría de código para confirmar que no existan variables, funciones, nombres de tablas o comentarios en otros idiomas.

## Governance

- Esta constitución representa la norma suprema de arquitectura, diseño, estándares de código y accesibilidad del proyecto.
- Cualquier modificación a los principios fundamentales o directrices aquí expuestas DEBE documentarse formalmente como una enmienda constitucional y justificar su impacto técnico.
- La versión de la constitución sigue Versionado Semántico (SemVer):
  - **MAJOR**: Modificaciones, reemplazos o eliminaciones incompatibles de principios centrales o cambios de stack tecnológico fundamental.
  - **MINOR**: Incorporación de nuevos principios, estándares de calidad o ampliación sustancial de lineamientos.
  - **PATCH**: Ajustes de redacción, correcciones tipográficas o clarificaciones operativas.
- Todo desarrollo, Pull Request o revisión de código DEBE verificar el estricto cumplimiento de estos artículos antes de ser aprobado.

**Version**: 1.1.0 | **Ratified**: 2026-08-19 | **Last Amended**: 2026-08-21
