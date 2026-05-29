#!/usr/bin/env bash
set -euo pipefail

if ! command -v jq >/dev/null 2>&1; then
  echo "jq is required" >&2
  exit 1
fi

REPO="${1:-yousung/blog}"
WORKFLOW_NAME="${2:-blog-publish}"
API_URL="https://api.github.com/repos/${REPO}/actions/runs?per_page=20"

raw="$(curl -fsSL "$API_URL")"

run="$(printf '%s' "$raw" | jq --arg wf "$WORKFLOW_NAME" -r '
  .workflow_runs
  | map(select(.name == $wf and .event == "push"))
  | sort_by(.created_at)
  | last
')"

if [ "$run" = "null" ] || [ -z "$run" ]; then
  echo "No push run found for workflow: $WORKFLOW_NAME"
  exit 2
fi

run_id="$(printf '%s' "$run" | jq -r '.id')"
status="$(printf '%s' "$run" | jq -r '.status')"
conclusion="$(printf '%s' "$run" | jq -r '.conclusion')"
sha="$(printf '%s' "$run" | jq -r '.head_sha')"
url="$(printf '%s' "$run" | jq -r '.html_url')"
created_at="$(printf '%s' "$run" | jq -r '.created_at')"

jobs="$(curl -fsSL "https://api.github.com/repos/${REPO}/actions/runs/${run_id}/jobs")"
failed_jobs="$(printf '%s' "$jobs" | jq -r '[.jobs[] | select(.conclusion == "failure") | .name] | join(", ")')"

echo "workflow=$WORKFLOW_NAME"
echo "run_id=$run_id"
echo "status=$status"
echo "conclusion=$conclusion"
echo "head_sha=$sha"
echo "created_at=$created_at"
echo "url=$url"
if [ -n "$failed_jobs" ]; then
  echo "failed_jobs=$failed_jobs"
fi
