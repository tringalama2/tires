# Domain Model

## Schema

### `vehicles`
```
id                  bigint PK (auto-increment)
user_id             FK → users
year                smallint
make                string(50)
model               string(50)
vin                 string(17) nullable
nickname            string(50)
tire_count          tinyint default 5
starting_odometer   unsignedMediumInteger
last_selected_at    timestamp nullable
soft_deletes, timestamps
```

### `tires`
```
id            uuid PK
vehicle_id    bigint NOT NULL FK → vehicles
label         string required (T1, T2 … user-defined)
brand         string nullable
model         string nullable
tin           string(12) nullable  — DOT serial
size          string nullable
purchased_on  date nullable
notes         text nullable        — static notes (not per-rotation)
status        tinyint (enum: Active=1, Retired=2)
timestamps
```

`label` is the only required field. `vehicle_id` is non-nullable — vehicle must exist first.

### `rotations`
One row per rotation event (not per tire).
```
id            uuid PK
vehicle_id    bigint NOT NULL FK → vehicles
rotated_on    date required
odometer      unsignedMediumInteger required
note          text nullable        — general rotation note
is_setup      boolean default false — true only for the initial tire-install event
is_swap       boolean default false — true for a retire+replace swap rotation
timestamps
```

### `placements`
Fact table. One row per tire per rotation.
```
id              uuid PK
rotation_id     uuid FK → rotations
tire_id         uuid FK → tires
from_position   string(2) nullable  — null on is_setup placements and on swap replacement tires
to_position     string(2) nullable  — null when a retiring tire leaves the vehicle in a swap
tread_center    decimal(4,1) required
tread_inner     decimal(4,1) nullable
tread_outer     decimal(4,1) nullable
note            text nullable
timestamps

UNIQUE(rotation_id, tire_id)
```

Positions are the `TirePosition` enum: `FL`, `FR`, `RL`, `RR`, `SPARE`.

## Rotation types

| Flag | Purpose | from_position | to_position | Permutation check |
|------|---------|--------------|------------|-------------------|
| `is_setup = true` | Initial install — establishes starting positions and baseline tread | null | set | Skipped |
| *(neither)* | Normal rotation — all 5 tires | set | set | Required |
| `is_swap = true` | Retire + replace — only the swapped pair(s) | retiring: set, replacement: null | retiring: null, replacement: set | Skipped |

## Primary keys

`vehicles` uses bigint (auto-increment). `tires`, `rotations`, `placements` use UUIDs.

## Key relationships

- `Vehicle` hasMany `Tire`, hasMany `Rotation`
- `Rotation` hasMany `Placement`
- `Tire` hasMany `Placement`
- `Placement` belongsTo `Rotation`, belongsTo `Tire`

## Tire status vs. installation — two orthogonal concepts

`status` (Active/Retired) is a lifecycle attribute on the tire itself. "Installed" is a positional
fact derived from placements — does this tire currently occupy a vehicle position?

| | Installed | Not installed |
|---|---|---|
| **Active** | Normal — on vehicle, in rotation | Purchased but not yet placed, or in storage |
| **Retired** | Transitional — flagged for swap but not yet done | End state — off vehicle, done |

Consequences:
- `Vehicle::activeTires()` — lifecycle scope. Returns tires with `status = Active`.
- `Vehicle::isSetupComplete()` — positional invariant. Counts placements in the setup rotation.
  **Never** substitute `activeTires()->count()` here — a vehicle with a retired tire still has
  all positions filled and must not be redirected to setup.
- `TireService::currentPosition()` — positional fact, derived from most recent placement.
