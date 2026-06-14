---
name: feedback-spare-was-intentional
description: SP code for Spare position was the user's deliberate choice, not a linter artifact
metadata:
  type: feedback
---

The change from 'SPARE' to 'SP' in TirePosition enum was made intentionally by the user, not by Pint. Do not attribute this (or similar enum value choices) to linter behavior. The protective phpcs comment added to guard 'SPARE' was unnecessary — remove that pattern if it appears again.

**Why:** User corrected the assumption mid-session.
**How to apply:** When the user makes a code choice that looks like it could be a linter artifact, don't assume — check with them or stay neutral.
