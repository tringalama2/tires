---
name: treadmark-design
description: Use this skill to generate well-branded interfaces and assets for Tread Mark (a tire-rotation & tread-wear tracking app for off-roaders, mechanics, and car enthusiasts), either for production or throwaway prototypes/mocks. Contains essential design guidelines, colors, type, fonts, assets, and UI-kit components for prototyping.
user-invocable: true
---

# Tread Mark — design skill

Read **`resources/design/README.md`** first — it holds the brand decisions, content fundamentals,
visual foundations, and iconography rules. Then explore the other files as needed:

- **`styles.css`** + `tokens/` — the brand tokens (color, type, spacing). Link `styles.css` to pick
  them all up, or copy the values.
- **`assets/`** — the Trail Tracks logo marks and app icon (SVG). Copy these out; never redraw them.
- **`components/`** — React/JSX components (Logo, Button, IconButton, Input, Select, Badge, Alert,
  Card, StatTile, PositionTag, TreadGauge, Icon). Each has a `.prompt.md` with usage. Use these for
  design artifacts, mocks, and prototypes. Icons come from the `Icon` component (inline Phosphor
  vectors) — never the icon font.
- **`blade/`** — production Blade + Tailwind components (`<x-treadmark.*>`) wired to
  `blade-phosphor-icons`, for the real Laravel app. See `blade/README.md`.
- **`ui_kits/tire-tracker/`** — an interactive recreation of the full product to reference for layout
  and composition.

## How to work

- **Visual artifacts** (slides, mocks, throwaway prototypes): write static HTML that links
  `styles.css`, loads Oswald / JetBrains Mono (Figtree too) from Google Fonts, and builds UI from
  the tokens and component patterns. Use the `Icon` component for icons (inline SVG — the Phosphor
  *font* renders blank in sandboxed previews). Copy assets out of `assets/`.
- **Production code**: copy the `blade/` components into the Laravel app, merge the Tailwind config,
  and follow the brand rules here to extend them.

Stay on brand: **Deep Pine + Hi-Vis Blaze**, Oswald/Figtree/JetBrains Mono, Phosphor icons, tread in
32nds, direct mechanic's voice, no emoji. The single accent is blaze orange — use it sparingly for
primary actions and the attention/fastest-wear state.

If the user invokes this skill without other guidance, ask what they want to build or design, ask a
few clarifying questions, then act as an expert designer who outputs either HTML artifacts or
production code depending on the need.
