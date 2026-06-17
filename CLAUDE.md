# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> Handoff from a spreadsheet prototype. This file is the entry point for Claude Code.
> Read `docs/business-logic.md` BEFORE building any reporting or rotation-entry code —
> it contains three non-obvious rules the spreadsheet prototype already solved. Do not
> re-derive them from scratch.

## 1. Purpose

A small personal web app to track tire rotations and tread wear for a Toyota 4Runner whose
spare tire is part of the rotation — so there are 5 tires moving across 5 positions
(Front L, Front R, Rear L, Rear R, Spare).

It must answer two questions the owner cares about:

1. By position — which positions wear fastest? (e.g., is the right front eating tires?)
2. By tire — for each physical tire: where is it now, what's its tread, how fast is it
   wearing, and what notes were logged about it over time.

Secondary goal: make data entry at each rotation as fast as possible (it happens in a
driveway/garage, often on a phone).

## 2. Stack

- Backend: Laravel 13, PHP 8.4. DB: SQLite for dev, Postgres for prod.
- Frontend: pick ONE (domain logic stays in services so this is swappable):
    - Livewire 4 — recommended. Fastest path for a CRUD-ish personal app, server-rendered,
      trivially mobile-responsive. Best if the app is used in a mobile browser.
- Tests: Pest. The wear math and rotation integrity are the crux — cover them first.

## 3. Domain model (full detail in docs/domain-model.md)

- tire — a physical tire (T1…Tn). Brand/model, DOT serial, purchase + install info, new-tread
  depth, active/retired status. Identity is explicit and persistent — the single most important
  upgrade over the spreadsheet.
- rotation — one rotation event: date + odometer (+ optional general note).
- placement — the fact table: one row per tire per rotation. Records the tire, from_position,
  to_position, the tread reading(s) taken at removal, and a tire-specific note. All reporting
  derives from this table.
- position — fixed set: FL, FR, RL, RR, SPARE. Model as an enum (owner does not need
  left/right/front/rear roll-ups).

## 4. The three non-obvious rules (details in docs/business-logic.md)

1. Tire identity is explicit. The spreadsheet had to infer which physical tire was which by
   chaining each rotation's to_position into the next rotation's from_position. In the app this
   is solved for free: every placement references a tire_id. Never infer identity.
2. Wear is attributed to the from_position. A tread reading is taken when a tire is removed (at
   its from_position). Wear for an interval = (same tire's previous center reading) − (this
   reading), charged to the from_position it just occupied, normalized to per-1,000-miles using
   the odometer delta between the two rotations. A tire's first placement has no prior → no wear row.
3. The next rotation auto-seeds itself. When the user starts a new rotation, pre-fill, for each
   active tire, from_position = that tire's current position (= to_position of its latest
   placement). The user then only picks each to_position and enters tread. Integrity check: the
   set of to_positions in one rotation must be a permutation of the from_positions.

## 5. Conventions

- Tread depths are in 32nds of an inch, stored as decimals to allow halves. tread_center is
  required; tread_inner / tread_outer are optional (capture the inner-vs-outer scalloped wear).
- Keep derived logic (current position, wear rates, report aggregation, next-rotation seeding)
  in service classes (RotationService, WearReportService), not controllers/Blade. Mirror the
  spreadsheet formulas there.
- Measurements are hand-gauged and noisy (±1/32"). Prefer multi-rotation averages; don't present
  single-interval wear as precise. Spare should show ~0 wear — sanity check in tests.
- Design for multiple vehicles later (nullable vehicle_id now) but don't build vehicle UI yet.

## 6. Build order

See docs/roadmap.md. Short version: migrations + models + seeder (historical data) → rotation
entry with auto-seed → wear/report services → the two reports → polish/mobile. Write Pest tests
against the known-good outputs in docs/seed-data.md as you go.

## 6a. Commands

```bash
# Run all tests
php artisan test --compact

# Run a specific test file or filter by name
php artisan test --compact --filter=Phase2
php artisan test --compact tests/Feature/Phase1ServicesTest.php

# Format changed PHP files (required after every PHP edit)
vendor/bin/pint --dirty --format agent

# Inspect routes
php artisan route:list --except-vendor

# Read-only DB queries (prefer over tinker for data checks)
# Use the Laravel Boost MCP tool: database-query

# Asset bundling (Herd serves the site; run this if CSS/JS changes aren't visible)
npm run build
```

## 6b. Architecture

### Route → middleware → component flow

Routes in `routes/web.php` are layered through two custom middlewares:

1. `firstVehicleExists` — redirects to vehicle creation if the session has no vehicle.
2. `activeVehicleTires` — redirects to `vehicles.setuptires.index` if the vehicle's setup rotation doesn't have `tire_count` filled positions. All dashboard/rotation/report routes sit behind this gate. **Do not** use `activeTires()->count()` here — a vehicle with a retired tire still has all positions filled and must not be redirected to setup.

The `activeVehicle` is stored in the session via `App\Actions\SelectVehicle` and re-hydrated on each component mount.

### Livewire component pattern

All feature components use Livewire 4's **Single-File Component (SFC)** style — the PHP class is defined inline at the top of the Blade file with `new #[Layout('layouts.app')] class extends Component { ... };`. Class-based components in `app/Livewire/` are the exception (only `RotationDashboard` and the Breeze auth components use that pattern).

**Never use `protected Vehicle $vehicle` in SFC components.** Livewire only calls `mount()` on the initial page load; subsequent requests (wire:click, wire:model changes) re-hydrate public properties from the serialized snapshot but do NOT call `mount()` again. A `protected` property set in `mount()` will be uninitialized on every subsequent request, causing a PHP 8 typed-property error. The correct pattern is a private `vehicle()` method:

```php
private function vehicle(): Vehicle
{
    return Vehicle::findOrFail($this->vehicle_id);
}
```

Use this method in all `#[Computed]` properties and actions. `$vehicle_id` is a `#[Locked]` public property and survives re-hydration correctly.

### Tire status vs. installation — two orthogonal concepts

`status` (Active/Retired) is a **lifecycle** attribute on the tire itself. "Installed" is a **positional** fact derived from placements — does this tire currently occupy a vehicle position? They are independent:

| | Installed (has a current position) | Not installed |
|---|---|---|
| **Active** | Normal — tire is on the vehicle and in rotation | Purchased but not yet placed, or in storage |
| **Retired** | Transitional — flagged for replacement but not yet swapped out | End state — off the vehicle, done |

Consequences:
- `Vehicle::activeTires()` — lifecycle query. Use for "how many tires are still in service."
- `Vehicle::isSetupComplete()` — positional invariant. Use for the setup gate; checks that all `tire_count` slots have a placement in the setup rotation. **Never** use `activeTires()->count()` for this — a vehicle with a retired tire still has all positions filled.
- `TireService::currentPosition()` — derives the positional fact for a single tire from its most recent placement.

### The `is_setup` rotation

Every vehicle has at most one `Rotation` with `is_setup = true`, created during tire setup. It establishes each tire's starting position (`to_position`) and initial tread reading. It is excluded from wear calculations (`WearReportService`, `buildIntervals()`) but **included** in `TireService::currentPosition()` — the setup placement is the source of truth for position until the first real rotation overwrites it.

The setup rotation is also the gating signal for `activeVehicleTires` middleware: that middleware counts placements in the setup rotation to decide if setup is complete — **not** active tire count. This matters when a tire is retired: retiring a tire reduces `activeTires()->count()` but does not affect setup rotation placements, so the gate should not trigger.

**Test helper pattern**: when writing tests for routes behind `activeVehicleTires`, create a setup rotation with one placement per position. Failing to do so causes a 302 redirect even if the test vehicle has the correct number of active tires.

### Service layer responsibilities

- **`TireService`** — single method: `currentPosition(Tire)` returns the `to_position` of the tire's most recent placement (any rotation, ordered by odometer desc).
- **`WearReportService`** — all reporting. `buildIntervals()` is the core: for each tire, it zips consecutive non-setup placements and attributes wear to the later placement's `from_position`. `wearByPosition()` and `wearByTire(?Vehicle, ?TireStatus $filterStatus = null)` aggregate from intervals. Pass `TireStatus::Active` or `TireStatus::Retired` to scope the tire set; `null` returns all. The dashboard and by-tire report both use this parameter — the dashboard always passes `TireStatus::Active`.
- **`RotationService`** — `startNext()` seeds stubs for a new rotation using current positions; `save()` validates the permutation constraint and persists atomically.

### Treadmark component library

All UI uses a custom design system in `resources/views/components/treadmark/`. Key components:
- `<x-treadmark.card>` / `<x-treadmark.stat-tile>` — dashboard cards
- `<x-treadmark.position-tag position="FR">` — renders the 2-letter abbreviation chip (pass `show-label` for the full name)
- `<x-treadmark.tread-gauge :depth="$value">` — visual tread depth bar
- `<x-treadmark.button>` / `<x-treadmark.alert tone="danger|warn">` — standard controls

Design tokens live in `resources/design/tokens/colors.css`. The skill at `.claude/skills/treadmark-design.md` documents the full system — activate it before any UI work.

### Tire swap (retire + replace)

When a tire is retired it is always immediately replaced. This creates a **swap rotation** —
a real rotation with `is_swap = true` that contains only the replaced pair(s), not all 5 tires.
The permutation integrity check is skipped for swap rotations.

Full spec: `docs/spec-tire-swap.md`. Key invariants:
- The retiring tire gets a placement with `to_position = null` (leaves the vehicle).
- The replacement tire gets a placement with `from_position = null`, `to_position = <vacated position>`.
- Both placements share the swap rotation. All changes are atomic via `RotationService::saveSwap()`.
- `WearReportService::buildIntervals()` includes swap placements — they are the final endpoint
  for a retiring tire's wear history and the starting anchor for a replacement tire's.
- `startNext()` already filters to `activeTires()`, so retired tires never appear as stubs.

### Rotation entry (prepare vs update)

Two separate Livewire SFCs handle rotation entry:
- `rotations/prepare.blade.php` — new rotation wizard, also handles edit when `edit_rotation_id` is set. Calls `RotationService::startNext()` to pre-seed `from_position` stubs; user sets `to_position` and tread per tire.
- `rotations/update.blade.php` — confirmation/review screen after save.

## 7. Where the data came from

The owner ran this as an Excel/Google-Sheets model first. docs/seed-data.md contains the 4 real
historical rotations (already resolved to tire IDs) and the report values the app must reproduce.
Load it in DatabaseSeeder so the app boots with real data and the tests have ground truth.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines
should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an
expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/breeze (BREEZE) - v2
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you
work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling
  files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature
  tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`,
  `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual
  alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before
  sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on
  installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most
  relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not
  `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to
  discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`,
  `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`,
  `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests
  with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty
  zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters:
  `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and
  scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool
  to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`,
  `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests
  to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a
  specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list
  available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the
  correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other
  things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you
  should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be
  used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to
  use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit`
  to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run
  `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to
  ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting
  issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest`
  instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
