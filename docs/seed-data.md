# Seed Data & Known-Good Outputs

The 4 real historical rotations from the spreadsheet prototype, resolved to explicit tire IDs.
Positions use enum codes FL,FR,RL,RR,SPARE. Center tread in 32nds. Inner/outer not measured
historically (null).

## Tires

T1 = Falcon Wildpeak A/T3W (1 note) | T2 = Falcon Wildpeak A/T3W (2 notes) | T3 = Falcon Wildpeak A/T3W (1 note) | T4 =
Falcon Wildpeak A/T3W (0 notes) | T5 = Falcon Wildpeak A/T3W (1 note)
Brand/model/DOT/install were mostly blank in the prototype; owner fills over time.

## Rotations & placements (date | odometer | tire | from | to | center | note)

2024-11-17 | 104400 | T1 | RL | FL | 9 |
2024-11-17 | 104400 | T2 | RR | FR | 9 |
2024-11-17 | 104400 | T3 | SPARE | RL | 15 |
2024-11-17 | 104400 | T4 | FL | RR | 14 |
2024-11-17 | 104400 | T5 | FR | SPARE | 11 |
2025-07-01 | 110133 | T1 | FL | SPARE | 8 | scalloped (inner>outer) wear / vibrating steering wheel since start of
rotation
2025-07-01 | 110133 | T2 | FR | RL | 8 | mild scalloped (inner>outer) wear
2025-07-01 | 110133 | T3 | RL | FR | 14 | moved everything down to 30psi; chalk test suggests 30 or less
2025-07-01 | 110133 | T4 | RR | FL | 13 |
2025-07-01 | 110133 | T5 | SPARE | RR | 11 |
2026-01-29 | 115728 | T4 | FL | FR | 12 |
2026-01-29 | 115728 | T3 | FR | FL | 12 |
2026-01-29 | 115728 | T2 | RL | RR | 7 |
2026-01-29 | 115728 | T5 | RR | SPARE | 11 | inside more worn
2026-01-29 | 115728 | T1 | SPARE | RL | 9 |
2026-06-10 | 120495 | T3 | FL | RL | 12 |
2026-06-10 | 120495 | T4 | FR | RR | 10 |
2026-06-10 | 120495 | T1 | RL | FR | 7 |
2026-06-10 | 120495 | T2 | RR | SPARE | 6 | Now the shaking is back
2026-06-10 | 120495 | T5 | SPARE | FL | 9 |

## Known-good outputs (assert in tests)

Current position / latest center: T1 FR/7, T2 SPARE/6, T3 RL/12, T4 RR/10, T5 FL/9.
Wear by position (avg /1000mi): Front R 0.32 > Rear L 0.26 > Rear R 0.13 > Front L 0.12 > Spare 0.08.
Note counts: T1=1, T2=2, T3=1, T4=0, T5=1.
These come from the working spreadsheet — the regression baseline."
