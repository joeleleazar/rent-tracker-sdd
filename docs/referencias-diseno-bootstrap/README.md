# Referencias de Diseño Bootstrap 5

Estos 3 documentos son material de consulta que originó el Principio VI ("Sistema de
Componentes Visuales — Bootstrap 5") de `.specify/memory/constitution.md`. **No son
normativos por sí mismos** — la constitución es la fuente de verdad vinculante.

- `RESUMEN_EJECUTIVO_SPECS.md` — resumen ejecutivo, tabla de dependencias entre specs y roadmap.
- `ANALISIS_SPECS_PARA_VISTAS_BOOTSTRAP.md` — análisis detallado por spec, wireframes conceptuales.
- `GUIA_COMPONENTES_BOOTSTRAP.md` — snippets de componentes Bootstrap por spec.

## Excepciones conocidas

Donde estos documentos sugieren algo que ya fue reemplazado por una decisión implementada
en una spec posterior, la spec implementada prevalece:

- **Paleta de colores**: la paleta real y vinculante es `resources/css/bootstrap.scss`, no la
  tabla de colores de estos documentos (algunos de sus valores, como el amarillo de advertencia
  por defecto, no cumplen el contraste WCAG AA exigido por el Principio III).
- **Interactividad asíncrona**: el proyecto usa **htmx**, no Alpine.js como sugieren estos
  documentos (ver `specs/011-elevacion-diseno-async/research.md`).
- **Navegación**: el proyecto usa un sidebar fijo, no el navbar plano superior de los
  wireframes de estos documentos (ver `specs/010-migracion-interfaz-bootstrap`).
