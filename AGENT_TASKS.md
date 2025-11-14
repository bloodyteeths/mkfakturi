# Agent Tasks Overview (Placeholder)

- Owner: `Project Manager` agent.
- Purpose: Define and track tasks for all agents.
- MAX_TOKENS_HINT: 400

## Agents

1. Project Manager (`AGENT_PROJECT_MANAGER`)
2. Developer (`AGENT_DEVELOPER`)
3. Tester (`AGENT_TESTER`)

## High-Level Workflow

1. Project Manager
   - Analyze repository and requirements.
   - Update `REQUIREMENTS.md`.
   - Break work into tasks and subtasks.
   - Populate this file and roadmap files.
   - Trigger Developer and Tester in orchestrator config.
2. Developer
   - Implement tasks assigned in this file.
   - Update `AGENT_DEVELOPER_ROADMAP.md`.
3. Tester
   - Design and run tests.
   - Update `AGENT_TESTER_ROADMAP.md`.

## Task Table (Fill Me)

| ID | Title | Owner Agent | Description | Status | Notes |
|----|-------|-------------|-------------|--------|-------|
| T1 | `<FILL_IN>` | Project Manager | `<FILL_IN>` | planned | `<FILL_IN>` |
| T2 | `<FILL_IN>` | Developer | `<FILL_IN>` | planned | `<FILL_IN>` |
| T3 | `<FILL_IN>` | Tester | `<FILL_IN>` | planned | `<FILL_IN>` |

## Hand-off Conditions (Example)

- Project Manager -> Developer:
  - `REQUIREMENTS.md` drafted and reviewed.
  - `AGENT_TASKS.md` initial tasks ready.
- Developer -> Tester:
  - Code changes implemented and minimally self-tested.
  - Notes for Tester added in `AGENT_TASKS.md`.
- Tester -> Project Manager:
  - Tests executed (pass/fail noted).
  - Summary written into roadmap and `LOG.md`.

