# Multi-Agent Orchestrator Usage (Template)

MAX_TOKENS_HINT: 250

## 1. Prerequisites

- Python 3.9+ installed.
- `pip install openai pyyaml` (or your Agents SDK).
- `OPENAI_API_KEY` (or equivalent) set in environment.

## 2. Configure

1. Edit `orchestrator.config.yaml`:
   - `repo_path`: set to this repository root.
   - `model`: e.g., `gpt-4.1-mini` (recommended) or `gpt-4.1`.
   - `agent_count`: keep `3` for Project Manager, Developer, Tester.
   - `mode`: `serial` (default) or `parallel` (all three agents run once in parallel).
   - `sandbox_mode`: `workspace-write`.
   - `approval_policy`: `never`.
   - `log_file`: usually `LOG.md`.
2. Verify placeholder files exist (created by this template):
   - `REQUIREMENTS.md`
   - `AGENT_TASKS.md`
   - `AGENT_PROJECT_MANAGER_ROADMAP.md`
   - `AGENT_DEVELOPER_ROADMAP.md`
   - `AGENT_TESTER_ROADMAP.md`

## 3. Run

From the repo root:

```bash
python orchestrator.py --config orchestrator.config.yaml
```

- The script:
  - Loads config.
  - Instantiates three logical agents with concise system prompts.
  - Runs them in serial order: Project Manager → Developer → Tester.
  - Writes each agent’s markdown output into its roadmap file.
  - Appends one log line per agent to `LOG.md`.

## 4. Inspect Results

- Roadmaps:
  - `AGENT_PROJECT_MANAGER_ROADMAP.md`
  - `AGENT_DEVELOPER_ROADMAP.md`
  - `AGENT_TESTER_ROADMAP.md`
- Shared planning:
  - `REQUIREMENTS.md`
  - `AGENT_TASKS.md`
- Central log:
  - `LOG.md` (one line per major agent run with token estimate and summary).

## 5. Agent Prompt Templates

- See `AGENT_PROMPT_TEMPLATES.md`:
  - Master Orchestrator / Project Manager prompt.
  - Developer prompt.
  - Tester prompt.
- These prompts are:
  - Short, bullet-based.
  - Include `MAX_TOKENS_HINT` to enforce brevity.
