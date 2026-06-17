# Business Logic

Domain rules for wear calculation and rotation management. Read this before touching any
reporting or rotation-entry code.

## Rule A — Current position of a tire

The `to_position` of the tire's most recent placement (highest odometer rotation, any type).

```php
$tire->placements()
    ->join('rotations', 'rotations.id', 'placements.rotation_id')
    ->orderByDesc('rotations.odometer')
    ->value('to_position');
```

Known-good after 4 seeded rotations: T1→FR, T2→SPARE, T3→RL, T4→RR, T5→FL.

## Rule B — Wear attribution

A tread reading is taken when a tire is removed, describing wear at its `from_position`.
For each placement with a prior reading for the same tire:

```
interval_miles  = this.odometer − prev.odometer
center_wear_32  = prev.tread_center − this.tread_center   (positive = wore down)
wear_per_1000mi = center_wear_32 / interval_miles * 1000  (charged to this.from_position)
```

`prev` = same tire's placement at the previous rotation (next-lower odometer).
A tire's first placement has no prev → no wear row.

`WearReportService::buildIntervals()` orders each tire's placements by odometer, zips consecutive
pairs, and attributes each pair's wear to the later placement's `from_position`. Includes swap
rotations in the sequence — they are natural endpoints and anchors. Skips intervals where
`from_position` is null (replacement tire's swap placement is a start anchor, not an endpoint).

Readings are hand-gauged (±1/32"). Always report averages over multiple intervals, never
single-interval wear as precise data.

## Rule C — Wear by position

For each position P, over all valid intervals where `from_position = P`:
- count of intervals
- avg wear per 1,000 miles
- avg tread at removal

Known-good (avg wear /1000mi): FR 0.32 > RL 0.26 > RR 0.13 > FL 0.12 > SPARE 0.08.

## Rule D — Wear by tire

Per tire: current position (Rule A), latest center tread, lifetime avg wear/1,000 mi (Rule B
averaged), and all per-placement notes as "date: note" lines.

Known-good current position / latest center: T1 FR/7, T2 SPARE/6, T3 RL/12, T4 RR/10, T5 FL/9.
Note counts: T1=1, T2=2, T3=1, T4=0, T5=1.

## Rule E — Auto-seed next rotation + integrity

`RotationService::startNext()` generates one placement stub per **active** tire, where
`from_position` = that tire's current position (Rule A), ordered by `TirePosition::order()`
(FL, FR, RL, RR, SPARE). The user fills `to_position` and tread readings only.

Integrity rules enforced on save:
1. Exactly one placement per active tire.
2. Multiset of `to_positions` == multiset of `from_positions` (a permutation).
3. `tread_center` required, range 0–20 (32nds).
4. Rotation odometer > all previous rotation odometers.

Expected auto-seed from current seeded state: T5@FL, T1@FR, T3@RL, T4@RR, T2@SPARE.

## Rule F — Tire swap (retire + replace)

When a tire is retired it is always immediately replaced in one atomic operation via
`RotationService::saveSwap()`. A swap rotation (`is_swap = true`) is created with only the
replaced pair(s) — the permutation check is skipped.

- Retiring tire: placement with `from_position = currentPos`, `to_position = null`.
- Replacement tire: new `Tire` record (Active), placement with `from_position = null`,
  `to_position = currentPos`.
- Retiring tire's `status` is set to `TireStatus::Retired` in the same transaction.

`buildIntervals()` naturally handles this — the retiring tire's swap placement is its final
interval endpoint; the replacement tire's swap placement is its first anchor. No special
casing needed beyond skipping `from_position = null` intervals.

`startNext()` calls `$vehicle->activeTires()`, so retired tires never appear as stubs.
