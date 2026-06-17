# Spec: Tire Swap (Retire + Replace)

## Intent

When a tire wears out or is damaged, the user retires it and replaces it with a new tire
in one atomic operation. The replacement is always immediate — there is no "retire without
replacing" workflow.

## Domain model changes

### New column: `rotations.is_swap` (boolean, default false)

A swap rotation is a real rotation event — it has a date, odometer, and placements — but
it is **not** a permutation of all positions. It contains only the replaced tire(s). It needs
its own marker so:

- `RotationService::save()` can skip the permutation integrity check for swap rotations.
- `WearReportService::buildIntervals()` can treat swap placements correctly (see below).

### Tire status transition

The retiring tire's `status` is set to `TireStatus::Retired` as part of the atomic save.
Nothing else changes on the tire record — its last known `to_position` (from its most recent
non-swap placement) is intentionally left in place. `TireService::currentPosition()` will
return that position for a retired tire, which is acceptable: it means "last occupied."

---

## Data shape of a swap rotation

Given: T5 retires from position FL, T6 replaces it; T2 optionally also retires from SP,
T7 replaces it. Odometer = 121000.

```
Rotation
  is_swap = true
  odometer = 121000
  rotated_on = <user entry>

Placement (retiring tire T5 — optional tread)
  tire_id = T5
  from_position = FL   ← where T5 was (its currentPosition)
  to_position = null   ← leaves the vehicle
  tread_center = 5.5   ← optional final reading

Placement (replacement tire T6)
  tire_id = T6
  from_position = null ← comes from outside
  to_position = FL     ← takes the position T5 vacated
  tread_center = 15.0  ← new tire starting tread (required)
```

Both placements exist in the same swap rotation, one per pair.

---

## WearReportService changes

`buildIntervals()` currently processes all non-setup rotations.
After this change it must handle two cases:

1. **Retiring tire (T5):** include the swap placement as the _final_ interval endpoint.
   Interval = [last real rotation at FL] → [swap placement at FL]. Wear is attributed to FL.
   The swap placement has `to_position = null` so it won't seed any future position.

2. **Replacement tire (T6):** the swap placement is the _first_ anchor (baseline tread).
   T6's first wear interval starts here: [swap placement] → [next real rotation].
   `from_position` on that next real rotation = FL (where the swap placed T6).

The cleanest implementation: include `is_swap` rotations in the interval query, but exclude
them from `buildIntervals()` grouping **except** when they are the first or last placement
for that tire.

Concretely: `buildIntervals()` fetches placements ordered by odometer for each tire across
all non-setup rotations **including swap rotations**. The existing zip logic naturally handles
this — each consecutive pair of placements for a tire becomes one interval, regardless of
whether the rotation is real or swap.

The only guard needed: skip intervals where `from_position` is null (T6's swap placement is
the _start_ of an interval, not an endpoint — the next real rotation provides the from_position
that references back to the swap odometer via the odometer delta).

---

## RotationService changes

### `saveSwap(array $data, Vehicle $vehicle): Rotation`

New method. Does not call `validatePermutation()`. Shape of `$data`:

```php
[
  'rotated_on'  => string,       // date
  'odometer'    => int,          // >= last rotation odometer
  'swaps'       => [             // one entry per retire+replace pair
    [
      'retiring_tire_id'           => string,       // UUID of tire being retired
      'retiring_tread'             => float|null,   // optional final tread reading
      'replacement_label'          => string,       // new tire label (required)
      'replacement_brand'          => string|null,
      'replacement_model'          => string|null,
      'replacement_tread'          => float,        // new tire starting tread (required)
      'replacement_tin'            => string|null,  // DOT serial, max 12 chars
      'replacement_size'           => string|null,  // e.g. 275/70R18
      'replacement_purchased_on'   => string|null,  // date; defaults to today in UI
    ],
    ...
  ],
]
```

In one DB transaction:
1. Validate odometer >= last rotation's odometer (including swap rotations in the max check).
2. Create `Rotation` with `is_swap = true`.
3. For each swap pair:
   a. Create new `Tire` (Active) with label/brand/model.
   b. Look up retiring tire's current position via `TireService::currentPosition()`.
   c. Create placement for retiring tire: `from_position = currentPos`, `to_position = null`,
      `tread_center = retiring_tread` (null if not provided).
   d. Create placement for new tire: `from_position = null`, `to_position = currentPos`,
      `tread_center = replacement_tread`.
   e. Set retiring tire `status = TireStatus::Retired`.

---

## RotationService::startNext() — already correct

`startNext()` calls `$vehicle->activeTires()` which filters `status = Active`. A retired tire
is excluded automatically. No change needed.

---

## UI — Livewire SFC: `rotations/swap.blade.php`

Route: `GET rotations/swap/{vehicle_id?}` → `rotations.swap`  
Behind `activeVehicleTires` middleware.

### Layout

**Step 1 — Header (date + odometer)**
- Date field (defaults to today)
- Odometer field (validated >= last rotation on blur)

**Step 2 — Tire pairs (dynamic list)**
- Shows all active tires as a list
- Each row has a "Retire this tire" toggle
- Toggling one open reveals an inline replacement form:
  - Label (required), Brand, Model, Starting tread (required)
- Multiple tires can be toggled simultaneously (one swap rotation for all)
- An "+ Add another" affordance is not needed — the list is pre-populated with all active tires

**Save button** — disabled until:
- At least one tire is toggled for retirement
- All toggled tires have replacement label + starting tread filled
- Odometer is valid

### Cancel behavior
Full cancel — nothing is committed. Retiring tile toggles reset. Livewire component state
is discarded.

### Flow — two steps within the same Livewire component

The swap component is its own 2-step wizard. No separate update/confirm screen is used.
`rotations/update.blade.php` is for real rotations only — its drag UI and permutation logic
are incompatible with the swap data shape (retiring tire has `to_position = null`;
replacement tire has `from_position = null`).

**Step 1 — Entry** (`$step === 'entry'`)
- Date + odometer header
- List of all active tires with retire toggles
- Toggled tires expand an inline replacement form

**Step 2 — Review** (`$step === 'review'`)
- Summary of each pair: "T5 retiring from FL → T6 (15/32") taking FL"
- "Confirm & Save" button commits atomically via `RotationService::saveSwap()`
- "Back" returns to step 1 without discarding anything

### Success
After `saveSwap()` commits, redirect to `dashboard`.

---

## Validation rules

| Field | Rule |
|---|---|
| `rotated_on` | required, date, <= today |
| `odometer` | required, integer, >= last rotation odometer (including prior swaps) |
| `swaps` | array, min 1 entry |
| `swaps.*.retiring_tire_id` | required, exists in tires, belongs to vehicle, status = Active |
| `swaps.*.retiring_tread` | nullable, decimal, 1–20 |
| `swaps.*.replacement_label` | required, string, max 50 |
| `swaps.*.replacement_tread` | required, decimal, 1–20 |
| `swaps.*.replacement_tin` | nullable, string, max 12 |
| `swaps.*.replacement_size` | nullable, string |
| `swaps.*.replacement_purchased_on` | nullable, valid date string; UI pre-populates today |

---

## Tests to write / written

1. `RotationService::saveSwap()` creates rotation with `is_swap = true`. ✓
2. Retiring tire gets `status = Retired`. ✓
3. Retiring tire gets a placement with `to_position = null` and correct `from_position`. ✓
4. Replacement tire is created and placed at the vacated position. ✓
5. All changes are atomic — exception rolls back tire creation, status change, and rotation. ✓
6. Odometer < last rotation throws ValidationException. ✓
7. Multi-tire swap: two pairs, one rotation, four placements. ✓
8. `WearReportService::buildIntervals()` includes swap placements in tire intervals.
9. T5's final wear interval (last real rotation → swap) is calculated correctly.
10. T6's first wear interval (swap → next real rotation) starts from swap tread baseline.
11. `startNext()` excludes retired tire from stubs. ✓
12. UI: swap page renders with all active tires. ✓
13. UI: advancing to review step with valid entry. ✓
14. UI: validation errors on missing label, tread, TIN too long, invalid date. ✓
15. UI: save redirects to dashboard; retiring tire status = Retired. ✓
16. UI: back() returns to entry without losing form state. ✓
17. Replacement tire saves with tin, size, and purchased_on. ✓
18. TIN > 12 chars throws ValidationException at service layer. ✓
19. Invalid purchase date throws ValidationException at service layer. ✓
20. Purchase date defaults to today on mount. ✓

---

## Migration

```sql
ALTER TABLE rotations ADD COLUMN is_swap BOOLEAN NOT NULL DEFAULT FALSE;
```

Update `Rotation` model casts. Update `DatabaseSeeder` — no swap rotations in seed data,
no seeder change needed.

---

## Resolved decisions

- **No update/confirm screen for swaps.** `rotations/update.blade.php` is incompatible:
  it keys placements by `from_position` (null for a new tire), runs a permutation check
  (invalid for partial-position swaps), and presents a drag UI that has no meaning here.
  The swap component handles its own 2-step review inline.
- **"Active tires" count** in `vehicles/index.blade.php` uses `activeTires()->count()`.
  After a swap this correctly drops from 5 to 4 then back to 5. No change needed.
- **Reactivate removed.** There is no "reactivate" action. Retirement is permanent in the UI;
  re-entering a retired tire requires creating a new tire record.
