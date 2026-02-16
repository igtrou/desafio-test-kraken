#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/architecture/run_validation_pipeline.sh [options]

Options:
  --up                          Start full stack before validation
  --down                        Stop full stack after validation
  --include-incident            Run controlled incident rehearsal after smoke
  --report-dir PATH             Directory for report/log files
                                (default: storage/app/operations/architecture-reports)
  --incident-requests N         Incident load total requests (default: 120)
  --incident-concurrency N      Incident load concurrency (default: 8)
  --incident-timeout N          Incident load timeout seconds (default: 2)
  --incident-wait-alert-seconds N
                                Wait for firing alerts during incident (default: 0)
  --incident-alert-poll-interval N
                                Alert polling interval seconds (default: 10)
  --help                        Show this help

Examples:
  scripts/architecture/run_validation_pipeline.sh --up
  scripts/architecture/run_validation_pipeline.sh --up --include-incident
  scripts/architecture/run_validation_pipeline.sh --include-incident --down
EOF
}

UP_STACK=0
DOWN_STACK=0
INCLUDE_INCIDENT=0
REPORT_DIR="storage/app/operations/architecture-reports"
INCIDENT_REQUESTS=120
INCIDENT_CONCURRENCY=8
INCIDENT_TIMEOUT=2
INCIDENT_WAIT_ALERT_SECONDS=0
INCIDENT_ALERT_POLL_INTERVAL=10

while [[ $# -gt 0 ]]; do
    case "$1" in
        --up)
            UP_STACK=1
            shift
            ;;
        --down)
            DOWN_STACK=1
            shift
            ;;
        --include-incident)
            INCLUDE_INCIDENT=1
            shift
            ;;
        --report-dir)
            REPORT_DIR="${2:-}"
            shift 2
            ;;
        --incident-requests)
            INCIDENT_REQUESTS="${2:-}"
            shift 2
            ;;
        --incident-concurrency)
            INCIDENT_CONCURRENCY="${2:-}"
            shift 2
            ;;
        --incident-timeout)
            INCIDENT_TIMEOUT="${2:-}"
            shift 2
            ;;
        --incident-wait-alert-seconds)
            INCIDENT_WAIT_ALERT_SECONDS="${2:-}"
            shift 2
            ;;
        --incident-alert-poll-interval)
            INCIDENT_ALERT_POLL_INTERVAL="${2:-}"
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            echo "Unknown argument: $1" >&2
            usage
            exit 1
            ;;
    esac
done

for value in INCIDENT_REQUESTS INCIDENT_CONCURRENCY INCIDENT_WAIT_ALERT_SECONDS INCIDENT_ALERT_POLL_INTERVAL; do
    if ! [[ "${!value}" =~ ^[0-9]+$ ]]; then
        echo "Invalid integer option: $value=${!value}" >&2
        exit 1
    fi
done

if ! [[ "$INCIDENT_TIMEOUT" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
    echo "Invalid timeout value: INCIDENT_TIMEOUT=$INCIDENT_TIMEOUT" >&2
    exit 1
fi

for cmd in date tee mkdir basename; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "Required command not found: $cmd" >&2
        exit 1
    fi
done

for script in \
    scripts/architecture/up_full_stack.sh \
    scripts/architecture/smoke_all_services.sh \
    scripts/architecture/incident_rehearsal.sh
do
    if [[ ! -x "$script" ]]; then
        echo "Required script not executable: $script" >&2
        exit 1
    fi
done

export WWWUSER="${WWWUSER:-1000}"
export WWWGROUP="${WWWGROUP:-1000}"

TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$REPORT_DIR"

REPORT_FILE="${REPORT_DIR}/validation-${TIMESTAMP}.md"
STAGE_ROWS_FILE="$(mktemp)"
FINAL_STATUS=0
SMOKE_STATUS=""

cleanup() {
    rm -f "$STAGE_ROWS_FILE"
}
trap cleanup EXIT

record_stage() {
    local stage="$1"
    local status="$2"
    local command="$3"
    local log_file="$4"

    echo "${stage}|${status}|${command}|${log_file}" >> "$STAGE_ROWS_FILE"
}

run_stage() {
    local stage="$1"
    local command_str="$2"
    shift 2

    local log_file="${REPORT_DIR}/${TIMESTAMP}-${stage}.log"
    local status="passed"

    echo "[pipeline] Running stage: ${stage}"
    echo "[pipeline] Command: ${command_str}"

    set +e
    "$@" 2>&1 | tee "$log_file"
    local exit_code=${PIPESTATUS[0]}
    set -e

    if [[ "$exit_code" -ne 0 ]]; then
        status="failed"
        FINAL_STATUS=1
    fi

    record_stage "$stage" "$status" "$command_str" "$log_file"
    return "$exit_code"
}

record_stage "pipeline" "info" "started_at=${TIMESTAMP}" "-"

if [[ "$UP_STACK" -eq 1 ]]; then
    if ! run_stage "stack_up" \
        "scripts/architecture/up_full_stack.sh up" \
        scripts/architecture/up_full_stack.sh up; then
        :
    fi
fi

if run_stage "smoke_all_services" \
    "scripts/architecture/smoke_all_services.sh" \
    scripts/architecture/smoke_all_services.sh; then
    SMOKE_STATUS="passed"
else
    SMOKE_STATUS="failed"
fi

if [[ "$INCLUDE_INCIDENT" -eq 1 ]]; then
    if [[ "$SMOKE_STATUS" == "passed" ]]; then
        if ! run_stage "incident_rehearsal" \
            "scripts/architecture/incident_rehearsal.sh --requests ${INCIDENT_REQUESTS} --concurrency ${INCIDENT_CONCURRENCY} --timeout ${INCIDENT_TIMEOUT} --wait-alert-seconds ${INCIDENT_WAIT_ALERT_SECONDS} --alert-poll-interval ${INCIDENT_ALERT_POLL_INTERVAL}" \
            scripts/architecture/incident_rehearsal.sh \
                --requests "$INCIDENT_REQUESTS" \
                --concurrency "$INCIDENT_CONCURRENCY" \
                --timeout "$INCIDENT_TIMEOUT" \
                --wait-alert-seconds "$INCIDENT_WAIT_ALERT_SECONDS" \
                --alert-poll-interval "$INCIDENT_ALERT_POLL_INTERVAL"; then
            :
        fi
    else
        record_stage \
            "incident_rehearsal" \
            "skipped" \
            "skipped because smoke failed" \
            "-"
    fi
fi

if [[ "$DOWN_STACK" -eq 1 ]]; then
    if ! run_stage "stack_down" \
        "scripts/architecture/up_full_stack.sh down --remove-orphans" \
        scripts/architecture/up_full_stack.sh down --remove-orphans; then
        :
    fi
fi

{
    echo "# Architecture Validation Report"
    echo
    echo "- Timestamp (UTC): ${TIMESTAMP}"
    echo "- Final status: $([[ "$FINAL_STATUS" -eq 0 ]] && echo "SUCCESS" || echo "FAILURE")"
    echo "- Include incident: $([[ "$INCLUDE_INCIDENT" -eq 1 ]] && echo "yes" || echo "no")"
    echo
    echo "| Stage | Status | Command | Log |"
    echo "| --- | --- | --- | --- |"

    while IFS='|' read -r stage status command log_file; do
        printf '| `%s` | `%s` | `%s` | `%s` |\n' "$stage" "$status" "$command" "$log_file"
    done < "$STAGE_ROWS_FILE"
} > "$REPORT_FILE"

echo "[pipeline] Report: ${REPORT_FILE}"

exit "$FINAL_STATUS"
