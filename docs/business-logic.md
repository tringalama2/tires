# Business Logic

Each rule shows the spreadsheet formula that proved it out, then the backend equivalent.
Reproduce the behavior; the formulas are the spec.

## A. Current position of a tire

The to_position of its latest placement (highest rotation odometer).
Spreadsheet: INDEX(To, MATCH(tire & ""|"" & MAXIFS(odometer, tireId, tire), toKey, 0))
Backend:
$tire->placements()->join('rotations','rotations.id','placements.rotation_id')
->orderByDesc('rotations.odometer')->value('to_position');
Known-good after 4 seeded rotations: T1->FR, T2->SPARE, T3->RL, T4->RR, T5->FL.

## B. Wear attribution (key metric)

A tread reading is taken when a tire is removed (describes wear at its from_position).
For each placement with a prior reading for the same tire:
interval_miles  = this.odometer - prev.odometer
center_wear_32  = prev.tread_center - this.tread_center      // positive = wore down
wear_per_1000mi = center_wear_32 / interval_miles * 1000     // charged to this.from_position
prev = same tire's placement at the previous rotation (next-lower odometer).
A tire's first placement has no prev -> no wear row.

Spreadsheet helper columns:
PrevMileage   = MAXIFS(odometer, tireId, thisTire, odometer, ""<"" & thisOdometer)
MilesInterval = thisOdometer - PrevMileage
CenterWear    = (prev center for thisTire at PrevMileage) - thisCenter
Wear/1000mi   = CenterWear / MilesInterval * 1000

Backend: in WearReportService, order each tire's placements by odometer, zip consecutive
pairs, attribute each pair's wear to the LATER placement's from_position.
Noise: readings are hand-gauged (+/-1/32""). Spare legitimately shows ~0.08/1000mi from noise.
Always report averages over multiple intervals.

## C. Report 1 - Wear by position

For each position P, over all valid intervals where from_position = P:
# intervals; avg_wear_per_1000mi; avg_tread_at_removal (avg tread_center removed from P).
Known-good (avg wear /1000mi): Front R 0.32 (fastest) > Rear L 0.26 > Rear R 0.13 >
Front L 0.12 > Spare 0.08.

## D. Report 2 - By tire

Per tire: current position (rule A), latest center tread, lifetime avg_wear_per_1000mi
(rule B averaged), and all notes as 'date: note' lines.
Known-good current position / latest center: T1 FR/7, T2 SPARE/6, T3 RL/12, T4 RR/10, T5 FL/9.
Note counts: T1=1, T2=2, T3=1, T4=0, T5=1.

## E. Starting a new rotation (auto-seed) + integrity

On new rotation, generate one placement stub per active tire with from_position = that tire's
current position (rule A), in Position::order(). User fills only to_position and tread.
Spreadsheet equivalent (fires when new row odometer entered):
From   = CHOOSE(rowInBlock, ""Front L"",""Front R"",""Rear L"",""Rear R"",""Spare"")
TireID = INDEX(tireId, MATCH(From & ""|"" & MAXIFS(odometer, To, From, odometer, ""<"" thisOdo), toKey, 0))
i.e. 'the tire most recently moved TO this position, before this rotation.'
Integrity rules on save (spreadsheet could not enforce):
1. Exactly one placement per active tire.
2. Multiset of to_positions == multiset of from_positions (a permutation across the 5 positions).
3. tread_center required, sane range (0-20 /32"").
4. New rotation odometer > previous rotation odometer.

## F. Tire replacement (future)

Retire old tire (status=retired), create new tire row, let it enter at a position. Identity is
explicit so history stays intact; new tire's first placement simply has no prior reading."
