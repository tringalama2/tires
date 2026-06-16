# Tread Mark — Design System

> **Every rotation, on the record.**
> The brand and component system for **Tread Mark**, an app for tracking tire rotations and
> tread wear — built for off-roaders, mechanics, and car enthusiasts.

This project is the design system: brand foundations (color, type, spacing), reusable UI
components in **two forms** (React/JSX for design + prototyping, Blade/Tailwind for the production
Laravel app), foundation specimen cards, and an interactive UI-kit recreation of the product.

---

## Product context

Tread Mark logs each tire **rotation** and the **tread reading** taken at every wheel position, in
32nds of an inch. From that history it answers two questions the owner actually cares about:

1. **By position** — which corners wear fastest? (Is the right front eating tires?)
2. **By tire** — for each physical tire (T1…T5): where is it now, what's its tread, how fast is it
   wearing, and when will it hit the 2/32" legal limit?

A vehicle rotates **4 or 5 tires** (the spare can be in the rotation), across positions
**FL · FR · RL · RR · SP**. Data entry happens in a driveway or garage, often on a phone, so speed
and big tap targets matter. The signature interaction is reading tread at each position, then
rotating tires to new positions.

### Sources

This system was built by reading the real product codebase. The reader is encouraged to explore it
for the authoritative schema, services, and Livewire flows:

- **GitHub:** `https://github.com/tringalama2/tires` — Laravel 13 · Livewire · Tailwind 3 ·
  blade-phosphor-icons. Key references: `SPEC.md`, `docs/business-logic.md`, `docs/seed-data.md`,
  and `resources/views/livewire/**` (dashboard, `rotations/prepare`, `reports/by-position`,
  `reports/by-tire`).

The product's visual brand in the repo was stock Laravel Breeze; **Tread Mark is a new brand
identity** designed on top of it (see decisions below), keeping what the codebase already used:
Figtree, Phosphor icons, tread-in-32nds, the car-top-view diagram, and a red danger accent.

---

## Brand decisions (locked)

- **Name styling:** `Tread·Mark` — Oswald, uppercase, with a blaze-orange middot.
- **Logo:** "Trail Tracks" — three ascending A/T-tread chevrons, top one in blaze. See
  `assets/treadmark-mark.svg` (light), `…-mark-reverse.svg` (dark), `…-app-icon.svg`.
- **Palette:** "Deep Pine + Hi-Vis Blaze" — a cool, near-black pine-graphite ground so a saturated
  safety-orange (`#FF5400`) leaps off it. Steel blue carries data/links; red/gold/green carry
  wear-status semantics.
- **Tagline:** *Every rotation, on the record.*

---

## CONTENT FUNDAMENTALS

How Tread Mark talks. Think **trusted mechanic**, not marketer.

- **Voice:** direct, calm, confident, utilitarian. Short declaratives. It states facts and the next
  action, nothing more.
- **Person:** second person ("your tires", "check alignment"). The app addresses the owner.
- **Casing:** sentence case for body and labels. **Oswald UPPERCASE** for display headings, stat
  values, button labels, and position codes. Mono UPPERCASE for eyebrows/labels.
- **Numbers, units-first:** always show units inline and in mono — `7.5/32"`, `120,495 mi`,
  `0.32 /1k mi`, `30 PSI`, `DOT A1B2`. Tread is in 32nds with the `″` mark. Use `≈` for projections
  (`≈ 14,200 miles left`).
- **No hype:** no exclamation points, no emoji, no "Oops!"/"Get started now ✨". Problems are stated
  plainly with the likely cause and fix ("Front right is wearing 2× faster. Check alignment.").
- **Domain vocabulary:** rotation, placement, tread (center / inner / outer), wear rate, scalloping,
  position (FL/FR/RL/RR/SP), retire, the 2/32" limit.

Examples — see the **Voice & Tone** card (`guidelines/brand-voice.card.html`).

| Context | ✓ On brand | ✕ Off brand |
|---|---|---|
| Empty state | "No rotations logged yet. Add your first to start tracking wear." | "Oops! Nothing here yet 🎉" |
| Alert | "Front right is wearing 2× faster. Check alignment." | "Uh oh — something might be wrong!" |
| Button | "Log Rotation" | "Get Started Now ✨" |

---

## VISUAL FOUNDATIONS

- **Color vibe:** cool, dark, industrial. The ground is a near-black **pine graphite** (`--ink-900
  #0F1410`) with neutral gray-greens. The single hero accent is **Hi-Vis Blaze** (`#FF5400`) —
  recovery-strap / safety-vest orange — used sparingly for primary actions, the brand, and the
  fastest-wearing / attention state. Imagery (when used) should be warm and gritty against the cool
  UI, not cold.
- **Type:** three families. **Oswald** (condensed grotesque) for display — gauge / odometer /
  license-plate energy; uppercase, tight. **Figtree** for body & UI (carried from the codebase).
  **JetBrains Mono** for every numeric/measurement readout. The pairing is industrial-display +
  humanist-body + technical-mono.
- **Spacing:** 4px base grid. Generous but not airy — this is a data tool. Cards use 16–24px padding.
- **Radii:** modest and rugged — `md 10px` for controls, `lg 14px` for cards, `xl 20px` for app
  frames, `pill` for chips/tags. Never fully pill-shaped buttons.
- **Borders:** hairline `1px` in cool neutrals (`--border-subtle #E3E9E2`). Dividers over heavy
  separators. On dark surfaces, borders go to `--ink-600`.
- **Elevation:** low-spread, **cool-tinted** shadows (rgba of `#0A0D0A`). Cards rest on `shadow-sm`;
  hover lifts to `shadow-lg` with a 2px translate; modals use `shadow-xl`. Dark surfaces add a top
  inner highlight (`--shadow-inset-top`) instead of a drop shadow.
- **Backgrounds:** mostly flat — paper `#FBFCFA` for pages, white for cards, pine for inverse panels
  and auth. The one recurring motif is the **chevron-tread texture** (the logo's chevrons, tiled at
  ~6% opacity) on dark hero/auth surfaces. No gradients, no glassmorphism, no noise overlays.
- **Cards:** white surface, 1px subtle border, 14px radius, `shadow-sm`. An `inverse` variant uses
  the pine surface for hero stats. Header is an uppercase Oswald title with an optional right-aligned
  action; footer is a sunken strip.
- **Hover / press:** hover darkens fills one step (blaze-500 → 600) or adds a sunken wash on quiet
  controls; press is a **mechanical 1px translate-down** (`--ease-mech`) plus the next-darker shade.
  Focus is a 3px blaze ring at 40% (`--focus-ring`).
- **Motion:** brisk and mechanical. `--dur-fast 120ms` for control states, `--dur-normal 200ms` for
  surfaces, `--ease-standard` default, `--ease-out` for entrances, `--ease-mech` for the press snap.
  No bounces, no decorative looping animation. Tread gauges animate their fill width on mount.
- **Tread health:** a sequential 4-stop scale — **good** (green, >8) → **fair** (gold, 5–8) → **low**
  (blaze, 2–5) → **worn** (red, ≤2) — drives the `TreadGauge` fill and any tread value coloring.
- **Iconography:** see below.

---

## ICONOGRAPHY

- **System: Phosphor Icons** — the same family the real app uses (`blade-phosphor-icons`). Consistent,
  rounded, friendly-but-technical. This is the *only* icon system; do not mix in others.
- **Weights:** `regular` for most UI, `fill` for status/alert glyphs. (Active nav is shown by color,
  not weight.) Match the repo, which leans on fill for warnings.
- **In design / prototypes (JSX + cards):** use the **`Icon` component** — it inlines the real
  Phosphor vectors as SVG (colored via `currentColor`). `<Icon name="arrows-clockwise" />`,
  `<Icon name="warning-fill" />`. **Do not use the Phosphor icon *font*** — sandboxed previews block
  `::before` glyph content, so font icons render blank; the inlined `Icon` always works. `ICON_NAMES`
  lists the bundled set; add more by re-importing SVGs into `assets/{regular,fill}/` and rebuilding
  `components/icons/Icon.jsx`.
- **In production (Blade):** use the installed `blade-phosphor-icons` components —
  `<x-phosphor-arrows-clockwise />`, `<x-phosphor-warning-fill />` (server-rendered SVG, no sandbox
  issue). The Blade button/icon-button components take icons via slot.
- **Signature glyphs:** `arrows-clockwise` (rotate), `gauge` (dashboard), `warning-fill` /
  `warning-octagon-fill` / `warning-circle-fill` (wear & scalloping alerts), `jeep` (vehicle),
  `chart-bar` (reports), `calendar-check`.
- **Brand mark ≠ icon:** the Trail Tracks chevrons are the logo, supplied as SVG in `assets/`. Don't
  redraw them as an icon; use the asset or the `Logo` component.
- **No emoji. No unicode symbols as icons.** The `≈`, `″`, `×` characters are used as *typography*
  in data, not as icons.

---

## Index / manifest

**Foundations**
- `styles.css` — the single entry point consumers link. `@import`s only.
- `tokens/colors.css` · `tokens/typography.css` · `tokens/spacing.css` · `tokens/fonts.css`
- `assets/` — `treadmark-mark.svg`, `treadmark-mark-reverse.svg`, `treadmark-app-icon.svg`

**Components (React / JSX)** — under `components/`, consumed via `window.TreadMarkDesignSystem_b4620a`
- `brand/Logo` · `buttons/Button` · `buttons/IconButton` · `forms/Input` · `forms/Select`
- `feedback/Badge` · `feedback/Alert` · `layout/Card` · `layout/StatTile`
- `tires/PositionTag` · `tires/TreadGauge` (signature) · `icons/Icon` (inline Phosphor vectors)

**Components (Blade / Tailwind, production)** — under `blade/`
- `tailwind.config.js`, `treadmark.css`, and `resources/views/components/treadmark/*.blade.php`
  mirroring the JSX set 1:1 (`<x-treadmark.button>`, `.tread-gauge`, …). See `blade/README.md`.

**Specimen cards** — under `guidelines/` (Colors · Type · Spacing · Brand groups in the DS tab).

**UI kit** — `ui_kits/tire-tracker/` — interactive recreation: Login, Dashboard, Rotation entry,
Wear reports. See its `README.md`.

**Templates** (starting points consuming projects can pick) — under `templates/`
- `vehicle-setup/` — 2-step onboarding wizard (Vehicle → Tires)
- `dashboard/` — the vehicle dashboard (stats, wear alert, position diagram, nearing-replacement)
- `wear-report/` — printable branded wear report (by-position + by-tire tables, tread gauges)
- `assign-positions/` — rotation step 2: drag tires to their physical corners; toggles
  passenger-car (4) vs SUV-with-spare (5) chassis

**Why two component sets?** JSX is the design-system's live/preview layer (DS tab, cards, starting
points, throwaway mockups). Blade is what ships in the Laravel app. Both read the same tokens, so
they stay visually identical. See the note in `blade/README.md`.

---

## Caveats & substitutions

- **Fonts load from Google Fonts CDN.** Figtree is the real product font; **Oswald** and **JetBrains
  Mono** are brand additions (both free / open-source). Self-host via `tokens/fonts.css` if you have
  licensed files or need offline use.
- The brand identity (logo, palette, type pairing) was **designed here**, not taken from the repo —
  the repo shipped stock Breeze styling.
