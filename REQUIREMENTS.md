MAX_TOKENS_HINT: 400

# Local Multi-Agent Workflow Requirements

- Owner: PROJECT_MANAGER agent
- Purpose: Run a fully local, file-backed multi-agent workflow (PM, DEVELOPER, TESTER) inside this repo with no external APIs.

## Context

- Repository path: .
- Primary goal:
  - Coordinate three local agents to plan, implement, and test a small but real improvement.
- Constraints:
  - No external API or Agents SDK calls.
  - All artifacts stored as files in this repository.
  - Keep changes minimal and consistent with existing codebase.

## Functional Requirements

1. Planning & Coordination
   - PROJECT_MANAGER maintains `REQUIREMENTS.md`, `AGENT_TASKS.md`, and `AGENT_PROJECT_MANAGER_ROADMAP.md`.
   - Each agent appends a log line to `LOG.md` on each major run.
2. Implementation
   - DEVELOPER improves the local orchestrator (`orchestrator.py`) so it is clearly local-only and documented.
   - DEVELOPER aligns roadmap files and logging with the required format (Task, Subtasks, Status, LastUpdate, Notes).
3. Testing
   - TESTER runs basic sanity checks:
     - Executes `python3 orchestrator.py --config orchestrator.config.yaml`.
     - Verifies roadmap files and `LOG.md` are updated as expected.

## Acceptance Criteria

- `REQUIREMENTS.md`, `AGENT_TASKS.md`, and all three `AGENT_*_ROADMAP.md` files exist and follow the shared roadmap format.
- `orchestrator.py` contains no external API calls and documents itself as local-only.
- Running `python3 orchestrator.py --config orchestrator.config.yaml` completes without errors and updates `LOG.md`.

## Self-Audit Report (PROJECT_MANAGER)

- TOKEN_ESTIMATE: 250
- Lessons_for_next_run:
  - Use very small, self-contained features for multi-agent demos.
  - Keep all requirements close to concrete file paths and commands.
