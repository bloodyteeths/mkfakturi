"""
Multi-agent orchestration template using an LLM backend.

This script instantiates three logical agents (Project Manager, Developer, Tester),
feeds them concise prompts, and writes their outputs into roadmap and log files.

Plug-in points (search for TODO markers):
- Repository path (or use config `repo_path`)
- API key (environment, e.g., OPENAI_API_KEY)
- Model name (config `model`, e.g., "gpt-4.1-mini" or "gpt-4o-mini")
- Agent count and mode (config `agent_count`, `mode`)
- Parallel vs serial mode (template implements serial; parallel can be added)
- Logging / tracing hooks
- Sandbox mode and approval policy (documented in config; enforced by your runner)

Run example:
    python orchestrator.py --config orchestrator.config.yaml
"""

from __future__ import annotations

import argparse
import dataclasses
import datetime as dt
import json
import os
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from typing import Any, Dict, List, Optional

import yaml  # pip install pyyaml


@dataclasses.dataclass
class OrchestratorConfig:
    repo_path: Path
    model: str
    fallback_model: Optional[str]
    agent_count: int
    mode: str
    sandbox_mode: str
    approval_policy: str
    log_file: Path
    tracing_enabled: bool = False
    tracing_verbose: bool = False


@dataclasses.dataclass
class AgentSpec:
    name: str
    role: str
    roadmap_file: Path
    max_tokens_hint: int
    system_prompt: str


def load_config(path: Path) -> OrchestratorConfig:
    raw = yaml.safe_load(path.read_text())
    return OrchestratorConfig(
        repo_path=Path(raw["repo_path"]),
        model=raw.get("model", "gpt-4.1-mini"),
        fallback_model=raw.get("fallback_model"),
        agent_count=int(raw.get("agent_count", 3)),
        mode=str(raw.get("mode", "serial")),
        sandbox_mode=str(raw.get("sandbox_mode", "workspace-write")),
        approval_policy=str(raw.get("approval_policy", "never")),
        log_file=Path(raw.get("log_file", "LOG.md")),
        tracing_enabled=bool(raw.get("tracing", {}).get("enabled", False)),
        tracing_verbose=bool(raw.get("tracing", {}).get("verbose", False)),
    )


def now_iso() -> str:
    return dt.datetime.utcnow().replace(microsecond=0).isoformat() + "Z"


def append_log_entry(
    log_file: Path,
    agent_name: str,
    task_name: str,
    token_estimate: int,
    log_summary: str,
) -> None:
    log_file.parent.mkdir(parents=True, exist_ok=True)
    ts = now_iso()
    line = (
        f"{ts} | AGENT={agent_name} | TASK={task_name} | "
        f"TOKEN_ESTIMATE={token_estimate} | SUMMARY={log_summary.strip()}\n"
    )
    with log_file.open("a", encoding="utf-8") as f:
        f.write(line)


def estimate_tokens_from_text(text: str) -> int:
    """
    Very rough token estimate to avoid extra dependencies.
    Roughly assumes ~0.75 tokens per character / ~1.3 tokens per word.
    """
    words = len(text.split())
    return max(1, int(words * 1.3))


def run_llm(
    system_prompt: str,
    user_prompt: str,
    model: str,
    max_output_tokens: int = 800,
    extra: Optional[Dict[str, Any]] = None,
) -> str:
    """
    Local-only placeholder: external API calls are disabled.

    This function intentionally does NOT call any remote LLM or Agents SDK.
    It simply echoes a compact JSON summary of the prompts so the rest of the
    orchestration pipeline (roadmaps, logs) can be exercised locally.
    """
    payload = {
        "mode": "local-offline",
        "model": model,
        "system": system_prompt[:200],
        "user": user_prompt[:200],
    }
    return json.dumps(payload, indent=2)


def derive_task_name(agent: AgentSpec) -> str:
    if "Project Manager" in agent.name:
        return "Plan and coordinate"
    if "Developer" in agent.name:
        return "Implement code changes"
    if "Tester" in agent.name:
        return "Validate changes"
    return "Agent task"


def extract_log_summary_and_token_estimate(output: str, default_hint: int) -> (str, int):
    """
    Look for LOG_SUMMARY and TOKEN_ESTIMATE markers in the agent output.
    If missing, fall back to a short prefix and the hint.
    """
    token_estimate = default_hint
    log_summary = ""
    for line in output.splitlines():
        stripped = line.strip()
        if stripped.startswith("TOKEN_ESTIMATE"):
            try:
                _, value = stripped.split(":", 1)
                token_estimate = int("".join(ch for ch in value if ch.isdigit()) or default_hint)
            except Exception:
                token_estimate = default_hint
        if stripped.startswith("LOG_SUMMARY"):
            _, value = stripped.split(":", 1)
            log_summary = value.strip()
    if not log_summary:
        log_summary = (output.strip().splitlines() or ["no summary"])[0][:200]
    return log_summary, token_estimate


def write_roadmap_output(roadmap_file: Path, agent_output: str) -> None:
    """
    Overwrite the roadmap file with the agent's latest output.
    The agent should already format it as markdown with a Self-Audit Report.
    """
    roadmap_file.parent.mkdir(parents=True, exist_ok=True)
    roadmap_file.write_text(agent_output, encoding="utf-8")


def build_project_manager_prompt(repo_path: Path) -> str:
    return f"""
You are the Project Manager agent in a 3-agent workflow (Project Manager, Developer, Tester).
MAX_TOKENS_HINT: 700

Constraints:
- Be concise. Prefer bullet lists.
- Update and reason about, but do not rewrite verbatim:
  - REQUIREMENTS.md
  - AGENT_TASKS.md
  - AGENT_PROJECT_MANAGER_ROADMAP.md
- Assume repository root is: {repo_path}

Tasks for this run:
1. Refine or draft requirements.
2. Define or refine tasks for Developer and Tester.
3. Update your roadmap with statuses and timestamps.
4. Produce a Self-Audit Report and LOG_SUMMARY.

Output format (markdown):
1. `## Roadmap Update` section.
2. `## Shared Files Update` section (describe changes, not full content).
3. `## Self-Audit Report` section with:
   - What was done
   - What could be improved
   - TOKEN_ESTIMATE: <INT>
   - Lessons for next run
   - LOG_SUMMARY: <ONE_LINE_FOR_LOG_MD>
"""


def build_developer_prompt(repo_path: Path) -> str:
    return f"""
You are the Developer agent in a 3-agent workflow (Project Manager, Developer, Tester).
MAX_TOKENS_HINT: 800

Constraints:
- Be concise and code-focused.
- Respect existing coding conventions in the repository: {repo_path}

Tasks for this run:
1. Read the summarized tasks from AGENT_TASKS.md.
2. Plan minimal changes and record them in AGENT_DEVELOPER_ROADMAP.md.
3. Describe code edits (file paths, functions) in bullets.
4. Outline testing notes for the Tester.
5. Produce a Self-Audit Report and LOG_SUMMARY.

Output format (markdown):
1. `## Roadmap Update`
2. `## Implementation Summary`
3. `## Testing Notes`
4. `## Self-Audit Report` with:
   - What was done
   - What could be improved
   - TOKEN_ESTIMATE: <INT>
   - Lessons for next run
   - LOG_SUMMARY: <ONE_LINE_FOR_LOG_MD>
"""


def build_tester_prompt(repo_path: Path) -> str:
    return f"""
You are the Tester agent in a 3-agent workflow (Project Manager, Developer, Tester).
MAX_TOKENS_HINT: 700

Constraints:
- Be concise and test-focused.
- Prefer focused, high-signal tests over broad runs.

Tasks for this run:
1. Review requirements and Developer notes.
2. Propose a concrete test plan (commands + what they validate).
3. Summarize expected or observed results.
4. Suggest follow-up items for Project Manager / Developer.
5. Produce a Self-Audit Report and LOG_SUMMARY.

Output format (markdown):
1. `## Roadmap Update`
2. `## Test Plan`
3. `## Test Results`
4. `## Self-Audit Report` with:
   - What was done
   - What could be improved
   - TOKEN_ESTIMATE: <INT>
   - Lessons for next run
   - LOG_SUMMARY: <ONE_LINE_FOR_LOG_MD>
"""


def make_agents(cfg: OrchestratorConfig) -> List[AgentSpec]:
    root = cfg.repo_path
    return [
        AgentSpec(
            name="Project Manager",
            role="Plans tasks and coordinates Developer and Tester.",
            roadmap_file=root / "AGENT_PROJECT_MANAGER_ROADMAP.md",
            max_tokens_hint=600,
            system_prompt=build_project_manager_prompt(root),
        ),
        AgentSpec(
            name="Developer",
            role="Implements code changes and documents them.",
            roadmap_file=root / "AGENT_DEVELOPER_ROADMAP.md",
            max_tokens_hint=800,
            system_prompt=build_developer_prompt(root),
        ),
        AgentSpec(
            name="Tester",
            role="Designs and runs tests; reports results.",
            roadmap_file=root / "AGENT_TESTER_ROADMAP.md",
            max_tokens_hint=700,
            system_prompt=build_tester_prompt(root),
        ),
    ]


def run_agent_once(cfg: OrchestratorConfig, agent: AgentSpec) -> None:
    task_name = derive_task_name(agent)
    user_prompt = (
        "You are given the current repository context and shared planning files.\n"
        "Act once, produce the requested markdown sections, and stop.\n"
        "Do not include code fences around the entire output.\n"
    )

    output = run_llm(
        system_prompt=agent.system_prompt,
        user_prompt=user_prompt,
        model=cfg.model,
        max_output_tokens=agent.max_tokens_hint,
    )

    write_roadmap_output(agent.roadmap_file, output)
    log_summary, token_estimate = extract_log_summary_and_token_estimate(
        output, default_hint=agent.max_tokens_hint
    )
    append_log_entry(
        cfg.log_file,
        agent_name=agent.name,
        task_name=task_name,
        token_estimate=token_estimate,
        log_summary=log_summary,
    )


def run_serial(cfg: OrchestratorConfig, agents: List[AgentSpec]) -> None:
    """
    Simple serial execution:
    1. Project Manager
    2. Developer
    3. Tester
    """
    for agent in agents:
        run_agent_once(cfg, agent)


def run_parallel(cfg: OrchestratorConfig, agents: List[AgentSpec]) -> None:
    """
    Simple parallel execution using threads.
    All agents run once in parallel; use for independent tasks.
    """
    with ThreadPoolExecutor(max_workers=len(agents)) as executor:
        for agent in agents:
            executor.submit(run_agent_once, cfg, agent)


def parse_args() -> argparse.Namespace:
    ap = argparse.ArgumentParser(description="Multi-agent orchestration template.")
    ap.add_argument(
        "--config",
        type=str,
        default="orchestrator.config.yaml",
        help="Path to YAML configuration file.",
    )
    return ap.parse_args()


def main() -> None:
    args = parse_args()
    cfg_path = Path(args.config).resolve()
    cfg = load_config(cfg_path)

    # Ensure repo_path is absolute and change working directory for consistency.
    cfg.repo_path = cfg.repo_path.resolve()
    os.chdir(cfg.repo_path)

    agents = make_agents(cfg)
    if cfg.mode == "serial":
        run_serial(cfg, agents)
    elif cfg.mode == "parallel":
        run_parallel(cfg, agents)
    else:
        # Fallback to serial for unknown modes.
        run_serial(cfg, agents)


if __name__ == "__main__":
    main()
