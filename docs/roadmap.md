# Roadmap

Phased so each step is shippable and testable. Write Pest tests against docs/seed-data.md
outputs as you complete each phase.

## Phase 0 - Scaffold

- laravel new, pick DB (SQLite dev). Add Pest.
- Decide frontend (Livewire 3 recommended; see CLAUDE.md s2). Install it.

## Phase 1 - Data layer

- Migrations: tires, rotations, placements (see docs/domain-model.md).
- Models + relationships + Position enum (cast from_position/to_position).
- DatabaseSeeder loads docs/seed-data.json (5 tires, 4 rotations, 20 placements).
- Test: seeder loads; counts correct; unique(rotation,tire) holds.

## Phase 2 - Core services (spec in docs/business-logic.md)

- TireService::currentPosition(Tire) (rule A).
- WearReportService - per-interval wear (rule B), wear-by-position (rule C), by-tire (rule D).
- Tests: assert known-good outputs (current positions, Front R fastest ~0.32/1000mi, spare ~0.08,
  note counts). This is the regression net.

## Phase 3 - Rotation entry with auto-seed (rule E)

- RotationService::startNext() -> builds 5 placement stubs with from_position = each active
  tire's current position, in Position::order().
- Entry screen: user sets date + odometer, picks each to_position, enters tread.
- Validation (FormRequest): one row per active tire; to_positions are a permutation of
  from_positions; center required; odometer strictly increasing.
- Tests: startNext after seed yields T5@FL, T1@FR, T3@RL, T4@RR, T2@SPARE as from-positions;
  permutation validator rejects a duplicate/missing position.

## Phase 4 - Reports UI

- Wear-by-position table (sortable, highlight fastest).
- By-tire table: current position, latest tread, avg wear/1000mi, dated notes list.
- Optional: line chart of center tread vs odometer per tire.

## Phase 5 - Polish / mobile / future

- Mobile-first entry (large tap targets; used in a garage).
- Tire detail page (history + notes timeline). ✓
- Inner/outer tread capture + wear-pattern flag (inner vs outer delta). ✓
- Tire replacement (retire + replace swap workflow). ✓
- Later: multi-vehicle, export/backup.

## Guardrails

- Keep derived logic in services, not controllers/components.
- Treat single-interval wear as noisy; surface averages.
- Don't re-derive tire identity from From/To chains - identity is explicit now.
