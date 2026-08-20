<!--
Sync Impact Report
Version change: Initial Template → 1.0.0
Modified principles: N/A (Initial constitution ratification)
Added sections:
- Core Principles:
  * I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)
  * II. Nomenclatura y Código Estrictamente en Español
  * III. Accesibilidad Extrema y UX para Adultos Mayores (Senior-First)
  * IV. Pruebas Automatizadas Exhaustivas (Modelos y Controladores)
  * V. Integridad de Datos y Seguridad Transaccional
- Restricciones Técnicas y Estándares de Accesibilidad
- Flujo de Trabajo, Calidad y Criterios de Aceptación
- Governance
Removed sections: N/A
Follow-up TODOs: None
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

## Restricciones Técnicas y Estándares de Accesibilidad

- **Entorno de Ejecución**: PHP 8.2+ | Laravel 11.x | PostgreSQL 15+.
- **Frontend / Vistas**: Blade Templates o componentes desacoplados con CSS semántico accesible, respetando estrictamente las pautas WCAG 2.1 (Nivel AA/AAA).
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

**Version**: 1.0.0 | **Ratified**: 2026-08-19 | **Last Amended**: 2026-08-19
