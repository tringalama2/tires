# SPEC.md — Tire Rotation & Tread-Wear Tracker

_Generated 2026-06-13 from interview + codebase audit. Updated 2026-06-13 with vehicle onboarding flow analysis._

---

## 1. Project context

Personal web app for a single user tracking tire rotations on a Toyota 4Runner with 5 tires in rotation (FL, FR, RL, RR, SPARE). Migrated from Excel → Laravel 11 → now rebuilt on Laravel 13 / Livewire 4 with a corrected schema.

The four real historical rotations (2024-11-17 through 2026-06-10) in `docs/seed-data.md` are the ground truth. All services must reproduce the known-good outputs from that data.

---

## 2. Schema (breaking change from existing)

### What to discard

The existing `rotations` table conflates a rotation _event_ with a tire _placement_: one row per tire, no `to_position`. This is wrong. Wipe and rebuild.

### New schema

#### `vehicles`
Keep as-is. Uses bigint `id()` (not UUID) — consistent with existing migration and `user_id` FK. `tire_count` stays.

```
id                  bigint PK (auto-increment, existing)
user_id             FK → users
year                smallint
make                string(50)
model               string(50)
vin                 string(17) nullable  ← CHANGE: make nullable (see §16 gap #1)
nickname            string(50)
tire_count          tinyint default 5
starting_odometer   unsignedMediumInteger
last_selected_at    timestamp nullable
soft_deletes
timestamps
timestamps
```

#### `tires`
```
id            uuid PK
vehicle_id    bigint NOT NULL FK → vehicles  ← non-nullable; vehicle must exist first
label         string required (T1, T2 … user-defined)
brand         string nullable
model         string nullable
tin           string(12) nullable  -- DOT serial
size          string nullable
purchased_on  date nullable        ← CHANGE: was required, now nullable
notes         text nullable        -- static notes (not per-rotation)
status        tinyint (enum: Active=1, Retired=2)
timestamps
```

`purchased_on` is **nullable** — label is the only required field. `vehicle_id` is **non-nullable** — the existing onboarding flow (create vehicle → add tires) already enforces this order.

#### `rotations`
One row per rotation event (not per tire).

```
id            uuid PK
vehicle_id    bigint NOT NULL FK → vehicles  ← non-nullable; mirrors tire FK convention
rotated_on    date required
odometer      unsignedMediumInteger required
note          text nullable        -- general rotation note
timestamps
```

#### `rotations` (additional column)
```
is_setup        boolean default false     -- true only for the initial tire-install event
```

#### `placements`
Fact table. One row per tire per rotation.

```
id              uuid PK
rotation_id     uuid FK → rotations
tire_id         uuid FK → tires
from_position   string(2) nullable        -- null only on is_setup rotations
to_position     string(2) NOT NULL        -- enum: FL|FR|RL|RR|SP
tread_center    decimal(4,1) required     -- 32nds, allows .5
tread_inner     decimal(4,1) nullable
tread_outer     decimal(4,1) nullable
note            text nullable             -- per-tire per-rotation note
timestamps

UNIQUE(rotation_id, tire_id)
UNIQUE(rotation_id, to_position)
```

### Initial placement (tire setup event)

When tires are added during onboarding, a special "installation rotation" is created at `vehicle.starting_odometer` with `rotated_on = vehicle.created_at`. As each tire is added, a placement row is created on this rotation with:

- `from_position = null` (initial install — no prior position)
- `to_position = chosen_position` (where the tire starts)
- `tread_center = starting_tread` (baseline reading)

This "rotation 0" participates in wear calculation as the `prev` baseline for the first real rotation. Rule A (current position) correctly reads `to_position` of the latest placement, so tires at initial positions are found immediately.

The `from_position = null` constraint means: the placements migration allows `from_position` to be nullable. The permutation integrity check (Rule E) only applies to real rotation events, not the initial setup rotation. The setup rotation is identified by `is_setup = true` boolean flag on `rotations`.

### Primary keys
`vehicles` uses bigint (existing, keep). `tires`, `rotations`, `placements` use UUIDs.

### Normalization rationale
The rotation event (date, odometer, general note) is shared across all 5 placements — putting it in `rotations` avoids repeating it 5 times. The placement is where all per-tire-per-rotation data lives. This is the minimal correct model.

---

## 3. Domain model & business logic

Defined in `docs/business-logic.md`. Summary:

**Rule A — Current position:** `to_position` of a tire's latest placement (highest odometer rotation).

**Rule B — Wear attribution:** Wear for an interval = `prev.tread_center − this.tread_center`, charged to `this.from_position`. Normalized to per-1,000 miles using odometer delta between consecutive rotations for the same tire. First placement has no prior → no wear row.

**Rule C — Wear by position:** Aggregate all intervals grouped by `from_position`. Report: # intervals, avg wear/1000mi, avg tread at removal. Known-good: FR 0.32 > RL 0.26 > RR 0.13 > FL 0.12 > Spare 0.08.

**Rule D — By tire:** Current position, latest center tread, lifetime avg wear/1000mi, all per-placement notes as 'date: note' lines.

**Rule E — Auto-seed next rotation:** On starting a new rotation, pre-fill 5 placement stubs where `from_position` = each active tire's current position (Rule A). User provides date, odometer, `to_position` for each tire, and tread readings. Integrity: to_positions must be a permutation of from_positions; odometer must exceed all previous.

---

## 4. Services

Keep all derived logic in services, not controllers or Livewire components.

### `TireService`
- `currentPosition(Tire): Position` — Rule A.

### `RotationService`
- `startNext(?Vehicle): array` — Returns 5 placement stubs (Rule E). Each stub: `[tire, from_position]`. Ordered by `Position::order()` (FL, FR, RL, RR, SPARE).
- `save(array $data): Rotation` — Validates and persists rotation + 5 placements atomically.
- `validatePermutation(array $fromPositions, array $toPositions): bool` — Integrity check.

### `WearReportService`
- `wearByPosition(?Vehicle): Collection` — Rule C. Returns per-position: count, avg_wear_per_1000mi, avg_tread_at_removal.
- `wearByTire(?Vehicle, ?TireStatus $filterStatus = null): Collection` — Rule D. Returns per-tire: current position, latest tread (center/inner/outer), lifetime avg wear/1000mi, notes. Pass `TireStatus::Active` or `TireStatus::Retired` to scope; `null` returns all tires.
- `projectedReplacementMileage(Tire, float $limitTread = 2.0): ?float` — Using avg wear rate and latest tread, estimate miles until `limitTread` reached (2/32" default). Returns null if insufficient data.
- `scalpingFlag(Placement): bool` — True when `|tread_inner − tread_outer| ≥ 2` and both are non-null.

---

## 5. Vehicle & tire onboarding flow

The existing flow is mostly correct and should be preserved. Key changes noted below.

### Flow summary

```
Register → FirstVehicleMiddleware → vehicles.create → vehicles.store
  → vehicles.setuptires.index (TireSetupController)
    → Add tire at each position (create + store, one at a time)
      → ActiveVehicleTiresMiddleware gates dashboard/rotations until tire_count tires installed
```

### `FirstVehicleMiddleware` (keep as-is)

Checks session for active vehicle. If none, tries to load the most recently selected vehicle from DB. If no vehicle exists at all, redirects to `vehicles.create`. Exempts `vehicles.create`, `vehicles.store`, and `livewire.update` routes.

**No gaps.** Logic is correct.

### Vehicle creation (`vehicles.create` / `vehicles.store`)

**Gaps to fix:**

1. **VIN is required** — `VehicleCreateRequest` and `VehicleUpdateRequest` both enforce `'vin' => 'required'`. VIN should be `nullable|string|max:17`. Users often don't have VIN handy during initial setup. The vehicles migration column must also become nullable.

2. **`nickname` has no `required` HTML attribute** in `components/forms/vehicles.blade.php` but IS required in `VehicleCreateRequest`. Add `required` to the HTML input.

3. **`MAX_VEHICLES_PER_USER = 5`** is enforced in `VehiclePolicy::create()` but `VehicleCreateRequest` does not scope the check, meaning the policy check (`count($user->vehicles)`) counts soft-deleted vehicles. Use `$user->vehicles()->count()` (excludes soft-deleted) in the policy.

4. **`starting_odometer` HTML input has no `required` attribute** in the form. Add it to match the FormRequest rule.

### Tire setup (`TireSetupController`)

**Gaps to fix:**

5. **`TireSetupController::store` creates an old-schema `rotation` record** (with `starting_position`, `starting_odometer`, `starting_tread`) that will not exist in the new schema. Must be rewritten to:
   - Find or create the vehicle's `is_setup = true` rotation at `vehicle.starting_odometer`.
   - Create a `placement` on that rotation: `from_position = null`, `to_position = $tirePosition`, `tread_center = $request->starting_tread`.

6. **No duplicate position guard** — `TireSetupController::create` does not check if the chosen position already has a tire. A second tire could be added to the same position, violating the UNIQUE constraint. Add a check: if a placement already exists for `(setup_rotation_id, to_position = $tirePosition)`, redirect back with an error.

7. **`purchased_on` has `required` in the tire form view** (`components/forms/tires.blade.php` line 59). Remove it — purchase date is optional in the new schema. Update `TireRequest` accordingly.

8. **`TireSetupController::store` uses `$vehicle->created_at->toDateString()`** as `rotated_on` for the setup rotation. This is correct conceptually (the installation date), but `vehicle.starting_odometer` may differ from the true setup date. Keep this behavior — it is the best available approximation.

### `ActiveVehicleTiresMiddleware` (keep with minor fix)

Currently checks `installed_tires_count != tire_count` via the `installedTires` HasMany (status = Active). After schema change, "installed" still means `status = Active` — the middleware logic stays valid. No change needed to the middleware itself, but `Vehicle::installedTires()` must still return active tires correctly.

### `SelectVehicle` action (keep as-is)

Updates `last_selected_at` and stores vehicle in session. No changes needed.

### `VehiclePolicy` (minor fix)

- `create()` uses `count($user->vehicles)` which includes soft-deleted records. Change to `$user->vehicles()->count()`.

---

## 6. Rotation entry workflow

### Step 1 — `rotations/prepare` (Livewire)

**Pre-fill:** Date defaults to today. Odometer is blank (user types it — no prediction).

**Layout:** Date + odometer at top. Below: car-top-view SVG layout with 5 position cards (FL, FR, RL, RR, SPARE). Each card shows:
- Tire label (e.g., T1)
- Previous center tread reading in grey as hint (e.g., "Last: 9/32")
- Three tread inputs: Center (required), Inner (optional), Outer (optional)
- Note textarea (optional)

**On submit:** Validates all required fields, stores in session, redirects to Step 2.

### Step 2 — `rotations/update` (Livewire)

**Visual:** Car-top-view diagram. Tires displayed at current positions (from_positions from Step 1). User drags each tire card to its new position. A garage holding area holds displaced tires mid-drag.

**Touch support:** Full mobile touch drag support (touchstart/touchmove/touchend events), not just mouse drag.

**Alternate path:** Table fallback (dropdown per tire row) accessible via a toggle button. Same submit action.

**Validation (server-side on save):**
1. Exactly one placement per active tire.
2. `to_positions` multiset == `from_positions` multiset (permutation check).
3. `tread_center` in range 0–20 /32".
4. Odometer > all previous rotation odometers.

**On error:** Block save, show inline validation error. Never warn-and-allow.

**On success:** Redirect to dashboard.

### Editing past rotations

All rotations are editable. When editing a rotation that is NOT the most recent, show a confirmation warning: "Editing this rotation will recalculate all wear rates for subsequent rotations." User must confirm before saving.

---

## 6. Reports

### Report A — Wear by Position (`/reports/by-position`)

Table with columns:
- Position (FL, FR, RL, RR, SPARE)
- # Intervals
- Avg Wear / 1,000 mi (highlight fastest in orange/red)
- Avg Tread at Removal (32nds)

Sorted by avg wear descending by default (fastest-wearing first).

**Callout:** If one position is >2× the avg of the others, show an alert: "Front Right is wearing significantly faster. Check alignment or consider rotating more frequently."

### Report B — Wear by Tire (`/reports/by-tire`)

Per-tire table (one row per tire):
- Tire label + current position
- Latest tread: Center / Inner / Outer (show inner/outer scalloping warning icon if delta ≥ 2/32")
- Lifetime avg wear/1000mi
- Projected miles to 2/32" replacement threshold
- Notes (most recent note shown inline; expand for full history)

**Active / Retired toggle:** A button in the report header switches between showing active tires (default) and retired tires. The table and tread chart both follow the filter. Only one group is shown at a time.

**Scalloping flag:** When inner/outer delta ≥ 2/32", show warning icon with tooltip: "Uneven wear detected. Check tire pressure (target 30 PSI) and inspect alignment."

**Tread over time chart:** Below the table (or on tire detail), line chart: x = odometer, y = tread center (32nds), one line per tire. Follows the active/retired toggle — only shows tires matching the current filter.

**Tire detail page (`/tires/{tire}`):**
- Full rotation history table: date | odometer | from_pos | to_pos | tread (C/I/O) | note
- Tread over time chart (this tire only)
- Projected replacement mileage
- Static tire info (brand, model, DOT, purchase date, size) — editable inline

---

## 7. Dashboard (`/dashboard`)

Shows after login:
- **Last rotation card:** Date, odometer, miles driven since (current odometer unknown so show days elapsed instead).
- **Tires nearing replacement:** Any **active** tire projected to hit 2/32" within 10,000 miles flagged with projected mileage. Retired tires are excluded.

Does NOT show: current position diagram (too much noise for the dashboard), fastest-wearing position (that's in the report).

---

## 8. Tire management (`/tires`)

Simple list + add/edit.

**List:** Table of all tires (active + retired). Columns: Label | Brand/Model | Current Position | Latest Tread | Status | Actions.

- Active tire Actions: **Edit** (navigates to tire detail) | **Retire** (navigates to swap page).
- Retired tire Actions: **Edit** only. Retirement is permanent; there is no reactivate.

**Add tire form:** Label (required), Brand, Model, DOT/TIN (max 12 chars), Size, Purchase Date (all optional), Status (Active/Retired). Placeholders: Brand → "BF Goodrich", Model → "KO2", Size → "275/70R18", DOT/TIN → "DOT XXXX XXXX XX".

**Tire identity:** Tires are tracked by label (T1–T5). Users identify physical tires by position history, not markings. Label is purely logical.

---

## 9. Scalloping & wear pattern flagging

Auto-flag conditions (per placement):
- `|tread_inner − tread_outer| ≥ 2/32"` → scalloping warning.

Action suggestion shown in tooltip: "Uneven wear (inner vs outer). Likely causes: low tire pressure or alignment drift. Check pressure (target 30 PSI) and consider alignment inspection."

This is displayed on:
- The By Tire report (icon next to tread reading)
- The tire detail page placement history

---

## 10. Replacement threshold

Replacement limit: **2/32"** (legal minimum, user-selected).

Projection formula: `miles_remaining = (latest_center − 2.0) / avg_wear_per_1000mi * 1000`

Show as "≈ X,XXX miles to replacement" on By Tire report and tire detail. Show null / "—" if fewer than 2 intervals (insufficient data for reliable avg).

---

## 11. Mid-cycle tire replacement

Handled via the **tire swap workflow** (`rotations/swap`). When a tire is retired it is always immediately replaced in one atomic operation. A swap rotation (`is_swap = true`) is created containing only the replaced pair(s) — the permutation check is skipped.

The replacement tire form captures: Label (required), Brand, Model, Starting tread (required), DOT/TIN (optional, max 12 chars), Size (optional), Purchase Date (optional, pre-populated with today).

Full spec: `docs/spec-tire-swap.md`. Retirement is permanent — there is no reactivate action.

---

## 12. Auth

Single user. Breeze auth is already in place (login/register). All routes behind `auth` middleware. No roles, no sharing, no multi-user.

---

## 13. Build order (phases)

### Phase 0 — Schema reset + vehicle/tire onboarding fixes
- Wipe `tires` and `rotations` migrations. Write new migrations: `tires`, `rotations` (with `is_setup`), `placements` (nullable `from_position`).
- Make `vehicles.vin` nullable via new migration.
- Update `Position` enum: FL, FR, RL, RR, SPARE with `order()` and `camel()`/`snake()` helpers.
- Update models: `Tire`, `Rotation`, `Placement` with correct relationships and casts.
- `DatabaseSeeder`: load `docs/seed-data.json` (5 tires, 4 rotations, 20 placements). Seed must also create a vehicle and a setup rotation.
- Fix `VehicleCreateRequest` and `VehicleUpdateRequest`: make `vin` nullable.
- Fix `VehiclePolicy::create()`: use `$user->vehicles()->count()` (exclude soft-deleted).
- Fix `TireSetupController::store`: create placement on `is_setup` rotation instead of old rotation record.
- Fix `TireSetupController::create`: guard against duplicate position.
- Fix `TireRequest`: make `purchased_on` optional.
- Fix form HTML: add `required` to nickname and `starting_odometer` inputs in vehicles form; remove `required` from `purchased_on` in tires form.
- **Tests:** seeder loads; counts correct; unique constraints hold; known-good current positions match. Tire setup creates correct is_setup rotation + placement. Duplicate position in setup is rejected.

### Phase 1 — Core services
- `TireService::currentPosition()` (Rule A).
- `WearReportService::wearByPosition()` (Rule C) and `wearByTire()` (Rule D).
- `WearReportService::projectedReplacementMileage()`.
- `WearReportService::scallopingFlag()`.
- **Tests:** Known-good outputs from `docs/seed-data.md`. FR fastest at ~0.32, Spare ~0.08. Current positions T1→FR, T2→SPARE, T3→RL, T4→RR, T5→FL. Note counts T1=1, T2=2, T3=1, T4=0, T5=1.

### Phase 2 — Rotation entry
- `RotationService::startNext()` and `::save()` with permutation validator.
- Rebuild `rotations/prepare` Livewire component: date + odometer + tread (C/I/O) + note per position, previous tread hint.
- Rebuild `rotations/update` Livewire component: drag-and-drop (mouse + touch) + table fallback toggle.
- Edit existing rotation: same form pre-filled, confirmation warning if not most recent.
- **Tests:** startNext() yields correct from_positions; permutation validator rejects duplicates/missing; save creates rotation + 5 placements; odometer validation.

### Phase 3 — Reports
- `WearByPosition` Livewire page (`/reports/by-position`).
- `WearByTire` Livewire page (`/reports/by-tire`) with scalloping flags and projected mileage.
- Tire detail page (`/tires/{tire}`) with full history table and tread chart (Alpine.js + Chart.js or SVG sparklines).
- **Tests:** Report pages render; known-good values appear; scalloping flag triggers at ≥2/32" delta.

### Phase 4 — Dashboard + Tire management
- Dashboard: last rotation card + replacement threshold alerts.
- Tires list + add/edit form (label only required).
- **Tests:** Dashboard shows correct days-since-last-rotation; replacement alert triggers correctly.

### Phase 5 — Polish
- Mobile-first CSS review (large tap targets on prepare form).
- Touch drag-and-drop on update screen.
- Wear trend chart on tire detail page.
- Alignment/pressure suggestion in scalloping tooltip.

---

## 14. Preserved from existing codebase

- Breeze auth scaffolding (login, register, profile pages).
- `Position` enum structure (extend with `order()` if not present).
- `TireStatus` enum (Active/Retired).
- Car-top-view SVG component.
- Tire-draggable and tread-input blade components (adapt to new schema).
- Livewire two-step rotation flow concept (prepare → update).
- Vehicles model and migration (with `tire_count`).

---

## 15. Known-good regression baseline

From `docs/seed-data.md` (assert in Pest tests):

| Tire | Current Position | Latest Center Tread |
|------|-----------------|---------------------|
| T1   | FR              | 7/32"               |
| T2   | SPARE           | 6/32"               |
| T3   | RL              | 12/32"              |
| T4   | RR              | 10/32"              |
| T5   | FL              | 9/32"               |

| Position | Avg Wear /1000mi |
|----------|-----------------|
| FR       | 0.32 (fastest)  |
| RL       | 0.26            |
| RR       | 0.13            |
| FL       | 0.12            |
| SPARE    | 0.08            |

Note counts: T1=1, T2=2, T3=1, T4=0, T5=1.

Next rotation auto-seed from_positions: T5→FL, T1→FR, T3→RL, T4→RR, T2→SPARE.

---

## 16. Gaps in existing vehicle/tire onboarding — full list

Found during codebase audit. All must be fixed in Phase 0.

| # | File | Gap | Fix |
|---|------|-----|-----|
| 1 | `VehicleCreateRequest`, `VehicleUpdateRequest`, `vehicles` migration | `vin` is required; blocks setup if VIN unknown | Make nullable in FormRequest rules and add migration to drop NOT NULL |
| 2 | `components/forms/vehicles.blade.php` line 39 | `nickname` input lacks `required` HTML attribute but is required in FormRequest | Add `required` attribute |
| 3 | `components/forms/vehicles.blade.php` line 69 | `starting_odometer` input lacks `required` HTML attribute | Add `required` attribute |
| 4 | `VehiclePolicy::create()` | `count($user->vehicles)` includes soft-deleted vehicles in the MAX_VEHICLES_PER_USER check | Use `$user->vehicles()->count()` |
| 5 | `TireSetupController::store` | Creates an old-schema `rotation` row (`starting_position`, `starting_tread`) that won't exist after schema rebuild | Rewrite to find-or-create `is_setup` rotation, then create a `placement` with `from_position = null`, `to_position`, `tread_center` |
| 6 | `TireSetupController::create` | No guard against adding a tire to a position that already has one | Query for existing placement at `(setup_rotation, to_position)` before showing form; redirect with error if occupied |
| 7 | `components/forms/tires.blade.php` line 59, `TireRequest` | `purchased_on` has `required` HTML attribute and is required in FormRequest | Remove `required`; make nullable |
| 8 | `TireSetupController::store` | Uses `$vehicle->created_at->toDateString()` as setup rotation date — minor: creation timestamp differs from actual install date if user delays | Acceptable; document as known limitation |
