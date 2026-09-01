---
name: Rent Tracker
description: Panel de gestión de contratos de locación, recibos y medidores — Bootstrap 5 a medida, en español.
colors:
  primary: "#1e40af"
  accent: "#0e7490"
  success: "#166534"
  danger: "#991b1b"
  warning: "#92400e"
  info: "#0c4a6e"
  neutral-ink: "#111827"
  neutral-secondary: "#374151"
  neutral-border: "#d1d5db"
  neutral-surface: "#f9fafb"
  neutral-white: "#ffffff"
typography:
  body:
    fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.5
  headline:
    fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
    fontSize: "2rem"
    fontWeight: 700
    lineHeight: 1.2
  title:
    fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.3
  seccion:
    fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0.06em"
rounded:
  default: "0.375rem"
  pill: "50rem"
spacing:
  0: "0"
  1: "0.2625rem"
  2: "0.525rem"
  3: "1.05rem"
  4: "1.8375rem"
  5: "3.4125rem"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.neutral-white}"
    rounded: "{rounded.default}"
  button-danger:
    backgroundColor: "{colors.danger}"
    textColor: "{colors.neutral-white}"
    rounded: "{rounded.default}"
  card:
    backgroundColor: "{colors.neutral-white}"
    rounded: "{rounded.default}"
---

# Design System: Rent Tracker

## Overview

**Creative North Star: "La Oficina Clara" (The Clear Office)**

Rent Tracker is the back office for someone who manages rental properties, contracts, tenants, deposits and monthly receipts by hand today and needs a screen that never makes them double-check whether an amount, a date or a contract's status is what it says it is. This is Operate-mode software: the visitor completes a task (register a contract, emit a receipt, correct a meter reading), so scanability, consistency and native Bootstrap conventions outrank expressive flourish. Nothing here performs a brand; everything here proves a number.

The system is built on stock Bootstrap 5.3 (compiled from Sass source, never the precompiled CSS) with a deliberately narrow set of overrides: a higher-contrast semantic palette, a slightly more generous spacing scale, soft card elevation, and one signature accent color reserved for state ("this is active," "this is where you are"). It does not invent a parallel visual language on top of Bootstrap — every component a user meets is a named Bootstrap primitive (`card`, `badge`, `modal`, `table-responsive`, `breadcrumb`, `input-group`) used exactly the way Bootstrap's own documentation shows it, so the system stays legible to any future contributor who already knows Bootstrap.

**Key Characteristics:**
- Semantic color first: every status (pagado/pendiente/anulado, alquilable/no alquilable, activo/rescindido) is read from color + icon + text together, never color alone.
- One accent, used rarely: the signature teal (`#0e7490`) marks "this is the active navigation item," nothing else competes with it.
- Money is typeset, not just formatted: monetary and metered figures use tabular numerals so columns of `S/ 1,500.00` actually line up.
- Every destructive action stops for a named confirmation ("Sí, eliminar locación" / "No, cancelar"), never a bare "OK."

## Colors

High-contrast, WCAG AA-minimum palette — every value was deliberately chosen darker than Bootstrap's stock swatches so text stays ≥4.5:1 against white without needing a `-lg` or bold-weight crutch to stay legible.

### Primary
- **Deep Cobalt** (`#1e40af`): the one call-to-action color. Primary buttons, primary links, the active-state default `nav-pills` would use before the accent override. Reserved for "the main thing to do on this screen."

### Secondary / Signature Accent
- **Signature Teal** (`#0e7490`): the project's own accent, independent of the semantic palette below. Used exclusively for "where you are" — the active sidebar item — so it reads as wayfinding, not as another action color competing with Deep Cobalt.

### Semantic States
- **Confirmed Green** (`#166534`): success alerts, "pagado," "activo," "alquilable."
- **Alert Red** (`#991b1b`): error alerts, destructive buttons, "anulado," "rescindido."
- **Caution Amber** (`#92400e`): warning states — chosen deliberately darker than Bootstrap's default `#FFC107` because white text on stock amber fails contrast; this amber does not.
- **Informative Sky** (`#0c4a6e`): informational badges and callouts outside the success/danger/warning trio.

### Neutral
- **Ink** (`#111827`): body text, page background contrast anchor.
- **Slate** (`#374151`): secondary text, muted labels, breadcrumb separators, sidebar background.
- **Hairline** (`#d1d5db`): card borders, table borders, the dashed border on empty states.
- **Paper** (`#f9fafb`): page background, hover fill for tree-table rows.

### Named Rules
**The One Accent Rule.** The signature teal marks current location, never an action. If a second element on the same screen wants the accent, that is a sign it should be Deep Cobalt (an action) or a semantic color (a state) instead.

## Typography

**Body & Display Font:** Instrument Sans (self-hosted via the Vite fonts plugin — weights 400/500/600), falling back to `ui-sans-serif, system-ui, sans-serif`.

**Character:** A humanist, slightly rounded sans that stays legible at Bootstrap's stock 16px body size without needing to be enlarged — precise rather than decorative, chosen so the same face reads equally well in a data table, a form label and a page heading.

### Hierarchy
- **Headline** (700, `fs-2` / 2rem): page-level `<h2>` in the layout header slot — one per page, states what the page is for ("Locaciones," "Contrato #4 — Local 101").
- **Title** (700, `fs-3`–`fs-4` / 1.5–1.75rem): card and modal section headings ("Costos Fijos de Referencia," "¿Está seguro de eliminar…?").
- **Body** (400–600, 1rem): form labels (`fw-semibold`), dl/dt-dd detail pairs, table cells, button labels.
- **Label** (600, `small`, `text-secondary`): helper text under a field ("Suma de los 4 costos de arriba"), timestamps, secondary metadata.
- **Título de Sección** (700, 0.75rem, uppercase, 0.06em tracking, `text-secondary` — `.titulo-seccion`): a card's own discreet heading ("Resumen del Recibo," "Pagos"), used instead of the louder `fs-4 fw-bold` heading where the card's content (figures, a status pill) should carry more visual weight than its title. It is the heading itself, never a redundant label sitting above a separate, larger heading.

Body copy is short-form (labels, single sentences, table cells) throughout — there is no long-form reading surface in this system, so no 65–75ch measure constraint applies; forms instead cap their column at `max-width: 42rem` so a label and its input never stretch wider than a single glance.

### Named Rules
**The No-Decoration Rule.** Hierarchy comes from Bootstrap's stock weight/size steps (`fs-2` → `fs-5`, `fw-semibold` → `fw-bold`) alone — never from letter-spacing tricks, all-caps labels, or a second display face. One family, one job each for weight and size.

*Confirmed exception (specs/041, 2026-08-27):* the `.titulo-seccion` token (uppercase, tracked, `typography.seccion` in the frontmatter) is the one deliberate, user-confirmed departure — a card's own discreet heading where the card's content should outweigh its title. It replaces `fs-4 fw-bold` for that one role only; it is never layered as a second label above another heading.

## Layout

Fixed sidebar (280px, always visible ≥768px) + fluid content column, built from `d-flex flex-column flex-md-row`. Below 768px the sidebar is a Bootstrap `offcanvas-md`: a compact dark top bar (logo + `☰ Menú`) stays pinned to the viewport, and the full navigation slides in from the left as an off-canvas drawer over a dimmed backdrop, closing when a link is chosen (via `resources/js/bootstrap.js`, guarded to the `max-width: 767.98px` range so the static desktop rail is never hidden) or when the backdrop is tapped.

*Confirmed decision (2026-08-31):* this replaced the earlier always-visible horizontal strip, which — once its items wrapped on a phone — stacked the whole menu at the top of the page and pushed the actual content out of the first viewport. The drawer is the intentional pattern now; do not revert to an always-expanded mobile nav. Both layouts carry `<meta name="viewport" … viewport-fit=cover>` and the top bar pads with `env(safe-area-inset-top)` so the toggle clears a notch.

Content pages constrain themselves per task rather than filling the viewport: detail and form pages cap at `max-width: 42rem`–`48rem` inside a `container-xl`, keeping a single-column read on wide monitors instead of stretching a `dl` or form to an uncomfortable line length. List/table surfaces (the locación tree, contract history) are allowed the full column width since they carry tabular data, not prose.

Vertical rhythm runs on a single spacing scale (`$spacer: 1.05rem`, Bootstrap's own ×0.25/0.5/1/1.75/3.25 steps) — `gap-3` between stacked sections is the default rhythm across every page in the app; nothing hand-tunes pixel margins outside that scale.

## Elevation & Depth

Hybrid: mostly flat, with one soft shadow tier reserved for `.card` as the single surface that "lifts" off the page background. Shadows are ambient (soft-blurred, low-opacity, tinted from ink rather than pure black) and respond to hover rather than sitting static at full strength.

### Shadow Vocabulary
- **Resting** (`box-shadow: 0 .125rem .375rem rgba(17,24,39,.08)`): every `.card` at rest — enough to separate it from the `#f9fafb` page background without competing with content.
- **Raised** (`box-shadow: 0 .375rem 1rem rgba(17,24,39,.1)`): `.card:hover` — a small lift that signals interactivity on clickable cards (e.g. the recibo list) without implying every card is clickable.
- **Overlay** (`box-shadow: 0 .75rem 2rem rgba(17,24,39,.12)`): reserved for the largest overlay surfaces; not yet spent on a component, held for anything that needs to visibly float above a raised card.

### Named Rules
**The Response-Not-Rest Rule.** Elevation increases only in response to interaction (hover). A card never ships at its "raised" shadow by default — that tier is earned, not decorative.

## Shapes

Bootstrap's stock corner radius throughout (`--bs-border-radius`, 0.375rem) — cards, inputs, buttons, badges and the dashed empty-state box all share the same rounding, so nothing reads as a foreign component. The one deliberate exception is `.badge`, which keeps Bootstrap's pill shape (`rounded.pill`) to stay visually distinct from a button. Borders are hairline (1px, `#d1d5db`) and single-purpose: a card border, a table border, or — doubled to 4px and colored — the left rail of the contract-history timeline, the system's one intentional use of a heavier border-as-structure.

## Components

### Buttons
- **Shape:** Bootstrap default radius (0.375rem), stock `btn`/`btn-sm` sizing — never forced to `-lg` per the constitution unless a specific screen calls for it.
- **Primary:** Deep Cobalt fill, white text — the single highest-priority action per screen ("Guardar Contrato," "Nueva Locación").
- **Danger:** Alert Red fill, reserved for the destructive action inside a confirmation modal, always paired with an outline-secondary "No, cancelar" sibling so the safe choice is never the visually louder one.
- **Outline-secondary:** the default for every non-primary, non-destructive action ("Editar," "Ver Historial," "Cancelar").
- **Hover / Focus:** Bootstrap's stock darken-on-hover plus its default focus ring (already tuned to the custom `$primary`); no custom override layered on top.

### Cards / Containers
- **Corner Style:** 0.375rem.
- **Background:** white on the `#f9fafb` page background.
- **Shadow Strategy:** see Elevation & Depth — resting → raised on hover.
- **Border:** 1px `#d1d5db`, plus a `border-success` variant for an active contract's card in the locación index.
- **Internal Padding:** `card-body`, Bootstrap default, content stacked with `gap-3`.

### Inputs / Fields
- **Style:** stock `form-control` / `form-select`, hairline border, white fill. Every monetary field is wrapped in an `input-group` with a literal `S/` prefix rather than a placeholder, so the currency is never ambiguous even before the user types.
- **Focus:** Bootstrap's default focus glow, derived from the custom `$primary`.
- **Error:** `invalid-feedback` rendered unconditionally visible (`d-block`) beneath the field, red text, no icon duplication with the field itself.

### Navigation
- **Sidebar:** dark (`#111827`) vertical rail on desktop; below 768px it becomes an off-canvas drawer opened from a pinned dark top bar (see Layout). Inactive links are white-on-dark with no background; hover adds a soft 10%-white fill; the active route gets the Signature Teal fill — the sidebar is the accent color's only home in the system. Because Bootstrap forces `.offcanvas-md` back to a transparent background at ≥768px, `.sidebar-principal` sets its dark fill with `!important` so the static rail keeps its colour.
- **Breadcrumb:** Bootstrap `breadcrumb`/`breadcrumb-item`, truncated to the last 3 levels with a literal "…" lead-in for deeper hierarchies, so a deeply nested locación never pushes the trail off-screen.

### Estado Vacío (signature component)
The system's one recurring custom pattern: a centered, dashed-border block (`1px dashed #d1d5db`, 0.375rem radius) with a single support icon above a muted sentence, used everywhere a list can legitimately be empty (no locaciones, no contratos, no recibos, no lecturas yet). It deliberately avoids a card (no shadow, no white fill) so an empty state never looks like a piece of content — it reads as "nothing here yet," not as "here's a card about nothing."

### Mensaje / Alert
Bootstrap `alert-success`/`alert-danger`, always paired with a support icon (`bi-check-circle-fill` / `bi-exclamation-triangle-fill`) per the constitution's requirement that status feedback carry both color and an icon, never color alone. Rendered as a dismissible alert (`alert-dismissible fade show` + `btn-close`). It **auto-closes after at most 8 seconds** (specs/042), but the timer pauses while the pointer or keyboard focus is over the alert and restarts in full on leave, so a message is never lost while being read; the `btn-close` closes it immediately. Without JavaScript the alert stays persistent (graceful degradation). Field-level validation errors are the exception — those remain persistent next to their input until corrected.

### Barra de carga de navegación (bespoke)
A hairline (3px) fixed bar at the top edge of the viewport (specs/042), built from Bootstrap `progress`/`progress-bar` with the `$primary` fill — the only custom part is the fixed position and reduced height, in the same spirit as the "Estado Vacío" bespoke component. It appears when a boosted in-app navigation (a GET request) takes longer than a ~150ms anti-flicker threshold and is removed as soon as the new content is ready, the request fails, or it is aborted. Form submissions do **not** trigger it — they keep the disabled "Guardando…" button as their feedback — and the first hard page load relies on the browser's native indicator. Honors `prefers-reduced-motion` (no width animation) and never takes focus.

### Stat card (KPI tile)
Introduced at scale by the inicio panel (specs/043): a plain `.card` whose `.card-body` holds a small `text-secondary` label above one large figure (`fs-3`/`fs-5`, `fw-bold`, `.cifra` for tabular alignment, `S/` prefix on money, `text-danger` on a figure that represents debt). No icon, no sparkline, no delta — it states one number and what it is. Laid out in a `row g-3` of `col-6 col-lg-3` tiles so they read two-up on mobile and in a single band on desktop. It is a read-only display primitive: a stat card never contains a control.

## Do's and Don'ts

### Do:
- **Do** keep every component a stock Bootstrap primitive — `card`, `table-responsive` + `table-hover`, native `Modal`, `badge`, `input-group`, `breadcrumb` — per the constitution's Principio VI.
- **Do** pair every status badge and alert with both a semantic color and an icon.
- **Do** require a named, two-button confirmation ("Sí, eliminar X" / "No, cancelar") for every destructive action.
- **Do** apply `.cifra` (tabular numerals) to monetary and metered figures wherever more than one number needs to align in a column.
- **Do** keep the Signature Teal accent to exactly one job: marking current location in the sidebar.
- **Do** hold the auth and profile screens (Laravel Breeze origin) to the same system as the rest of the app: Spanish copy, the stock type ramp (no blanket `fs-5` body text), and the shared `x-input-label` / `x-text-input` / `x-primary-button` components. The guest layout (`layouts/guest-bootstrap`) gives each screen a `title` / `subtitle` slot for its one page heading.

### Don't:
- **Don't** introduce Alpine.js for write-interactivity — htmx (`hx-boost`) is the documented, binding choice (constitution Principio VI, specs/011).
- **Don't** nest a card inside a card, or reach for a bordered box around an already-bordered empty state.
- **Don't** use gradient text, hard offset (`4px 4px 0`) shadows, or a kicker/eyebrow above a heading — none of these were ever part of this system's world.
- **Don't** substitute an icon for a text label — icons are always a reinforcement of an explicit label, never a replacement (constitution Principio VI).
- **Don't** widen Bootstrap's stock control sizing (`-lg` variants, oversized tap targets) by default; the constitution intentionally retired that "Senior-First" minimum in favor of Bootstrap 5's own conventions.
