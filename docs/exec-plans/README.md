# Execution Plans

Working directory for agent execution plans (design docs for multi-step changes).

- `active/` — plans currently being executed (create on demand)
- `completed/` — finished plans kept for reference (create on demand)

Keep plans short: goal, constraints, ordered steps, verification commands. Delete or move a plan to `completed/` when the work has merged.
