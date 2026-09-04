# POS Pro Design System

This is an extraction of the existing Inertia/Vue interface. It documents the current
system so feature work preserves the POS Kacaw visual language instead of introducing
one-off styles.

## 1. Atmosphere & Identity

POS Pro is a quiet, practical operations console: dense enough for daily cashier and
owner work, but calm through neutral surfaces, concise Indonesian copy, and clear
semantic status. Its signature is restrained monochrome depth; color is reserved for
meaningful state, never decoration.

## 2. Color

### Palette

| Role | CSS token / utility | Light | Dark | Usage |
|---|---|---:|---:|---|
| Page surface | `surface` | `#f1f2f3` | `#1f252b` | App background |
| Raised surface | `surface-raised` | `#ffffff` | `#272f37` | Cards, inputs, sidebar |
| Sunken surface | `surface-sunken` | `#e9ebed` | `#191e23` | Hover and neutral state |
| Default border | `border` | `#dcdee0` | `#363f48` | Cards, table divisions |
| Strong border | `border-strong` | `#c3c7cb` | `#4a545e` | Form controls |
| Primary ink | `ink` | `#1c2024` | `#f3f5f7` | Core text |
| Muted/subtle ink | `ink-muted` / `ink-subtle` | `#5e646a` / `#838a91` | `#989fa8` / `#78808a` | Metadata and supporting text |
| Brand | `brand` / `brand-soft` | `#222933` / `#e9ebed` | `#e2e5e8` / `#303942` | Links, active navigation, focus |
| Success | `success` | `#1baf7a` | `#199e70` | Completed state |
| Warning | `warning` | `#eda100` | `#c98500` | Open Bill state and cautions |
| Danger | `danger` / `danger-soft` | `#e34948` / `#fdecec` | `#e66767` / `#3a1e1e` | Void and destructive actions |

Use semantic utilities only. A new color must first be added to
`resources/css/app.css` and this table; feature pages must not use raw palette classes.

## 3. Typography

Primary font: `Instrument Sans`, then the system sans stack. Interface body text is
`text-sm` (14px); page titles are `text-base` (16px) semibold; table and status labels
are `text-xs` (12px); amount emphasis is `text-base` semibold with `tabular-nums`.
Metadata uses muted ink, never size below `text-xs` except the existing 11px account
email in the global shell.

## 4. Spacing & Layout

The base unit is 4px. Common steps are 8, 12, 16, 20, 24, and 32px (`gap-2` through
`gap-8`, `p-4`, `p-5`, `lg:p-8`). The application uses a fixed 16rem sidebar above
`lg`, a sticky 64px top bar, and a fluid content area with 16px mobile / 32px desktop
padding. Tables own horizontal overflow; primary page content must not. Sales summary
uses a three-column grid at `sm`; the receipt detail becomes a 2:1 content/summary grid
at `lg`.

## 5. Components

### Card

- **Structure:** bordered raised `section`, optional header, padded or flush body.
- **States:** static surface; flush cards contain tables or filter bars.
- **Accessibility:** semantic heading when titled; divisions remain visible in both themes.

### Badge

- **Structure:** compact rounded text label.
- **Variants:** `neutral`, `brand`, `success`, `warning`, `danger`.
- **State contract:** status always includes text. Completed is success, Open Bill is
  warning (`Open Bill`), and void is danger (`Dibatalkan`).

### Data table

- **Structure:** semantic table in an overflow container; 20px cell padding.
- **States:** secondary columns hide below `md`; rows use a sunken hover surface.
- **Accessibility:** column headers use `scope="col"`; meaningful content must be
  available without relying on color.

### Sales presentation

- **Index:** receipt number is the primary link; an Open Bill label is subordinate
  metadata directly below it. Open Bills use `opened_at`; other sales use `sold_at`.
  Only void totals are struck through.
- **Show:** a held Open Bill presents snapshots and totals read-only, displays its
  warning status, held time, and source-device prefix, and omits payment/change facts
  that do not yet exist. It never exposes resume, pay, discard, or void controls.

## 6. Motion & Interaction

Existing controls use the default Tailwind `transition` for meaningful hover/focus
feedback. No feature-specific motion is required. Interactive controls retain visible
`focus-visible` outlines and respect the user agent's reduced-motion behavior.

## 7. Depth & Surface

Strategy: **mixed, restrained**. Cards use a default border plus `shadow-sm`; tables
and inputs use borders; hover and selected states use tonal shifts. Do not add stronger
shadows, gradients, or decorative surfaces for operational status.

## 8. Accessibility Constraints & Accepted Debt

### Constraints

- Target WCAG 2.2 AA: status is communicated by text as well as color.
- Inputs have an accessible label or `aria-label`; links and buttons preserve visible
  focus.
- Open Bills are explicitly read-only, so absence of cashier controls cannot be
  mistaken for a failed action.
- Indonesian operational copy must stay concise and not rely on an icon alone.

### Accepted Debt

| Item | Location | Why accepted | Owner / Exit |
|---|---|---|---|
| `DataTable` retains its legacy generic `Record<string, any>` constraint | `resources/js/Components/ui/DataTable.vue` | Shared component lies outside this feature's owned scope; changing it would be a broad type refactor. | Replace when the shared table contract is separately refactored. |
