#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/architecture/incident_rehearsal.sh [options]

Options:
  --gateway-url URL         Target KrakenD URL to stress
                            (default: http://localhost:8080/v1/public/quotation/BTC?type=crypto)
  --upstream-service NAME   Docker compose upstream service to stop/start (default: laravel.test)
  --requests N              Load test total requests (default: 120)
  --concurrency N           Load test concurrency (default: 8)
  --timeout N               Load test per-request timeout in seconds (default: 2)
  --request-id-prefix TXT   Request id prefix (default: phase4-incident-)
  --prometheus-url URL      Prometheus base URL (default: http://localhost:9090)
  --jaeger-service NAME     Jaeger service name (default: krakend_gateway)
  --jaeger-url URL          Jaeger traces URL (default: http://localhost:16686/api/traces)
  --app-url URL             Laravel direct URL for recovery check (default: http://localhost)
  --wait-alert-seconds N    Wait for firing alerts in Prometheus (default: 0, disabled)
  --alert-poll-interval N   Alert polling interval in seconds (default: 10)
  --metric-wait-seconds N   Max wait for Prometheus metric deltas (default: 90)
  --metric-poll-interval N  Poll interval for metric deltas (default: 5)
  --no-restore              Do not auto-start upstream service on exit
  --help                    Show this help

Examples:
  scripts/architecture/incident_rehearsal.sh
  scripts/architecture/incident_rehearsal.sh --wait-alert-seconds 240
  scripts/architecture/incident_rehearsal.sh --requests 60 --concurrency 4 --timeout 2
EOF
}

GATEWAY_URL="http://localhost:8080/v1/public/quotation/BTC?type=crypto"
UPSTREAM_SERVICE="laravel.test"
REQUESTS=120
CONCURRENCY=8
TIMEOUT_SECONDS=2
REQUEST_ID_PREFIX="phase4-incident-"
PROMETHEUS_URL="http://localhost:9090"
JAEGER_SERVICE="krakend_gateway"
JAEGER_TRACES_URL="http://localhost:16686/api/traces"
APP_URL="http://localhost"
WAIT_ALERT_SECONDS=0
ALERT_POLL_INTERVAL=10
METRIC_WAIT_SECONDS=90
METRIC_POLL_INTERVAL=5
AUTO_RESTORE=1

while [[ $# -gt 0 ]]; do
    case "$1" in
        --gateway-url)
            GATEWAY_URL="${2:-}"
            shift 2
            ;;
        --upstream-service)
            UPSTREAM_SERVICE="${2:-}"
            shift 2
            ;;
        --requests)
            REQUESTS="${2:-}"
            shift 2
            ;;
        --concurrency)
            CONCURRENCY="${2:-}"
            shift 2
            ;;
        --timeout)
            TIMEOUT_SECONDS="${2:-}"
            shift 2
            ;;
        --request-id-prefix)
            REQUEST_ID_PREFIX="${2:-}"
            shift 2
            ;;
        --prometheus-url)
            PROMETHEUS_URL="${2:-}"
            shift 2
            ;;
        --jaeger-service)
            JAEGER_SERVICE="${2:-}"
            shift 2
            ;;
        --jaeger-url)
            JAEGER_TRACES_URL="${2:-}"
            shift 2
            ;;
        --app-url)
            APP_URL="${2:-}"
            shift 2
            ;;
        --wait-alert-seconds)
            WAIT_ALERT_SECONDS="${2:-}"
            shift 2
            ;;
        --alert-poll-interval)
            ALERT_POLL_INTERVAL="${2:-}"
            shift 2
            ;;
        --metric-wait-seconds)
            METRIC_WAIT_SECONDS="${2:-}"
            shift 2
            ;;
        --metric-poll-interval)
            METRIC_POLL_INTERVAL="${2:-}"
            shift 2
            ;;
        --no-restore)
            AUTO_RESTORE=0
            shift
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

for value in REQUESTS CONCURRENCY WAIT_ALERT_SECONDS ALERT_POLL_INTERVAL METRIC_WAIT_SECONDS METRIC_POLL_INTERVAL; do
    if ! [[ "${!value}" =~ ^[0-9]+$ ]]; then
        echo "--${value,,} must be a non-negative integer" >&2
        exit 1
    fi
done

if ! [[ "$TIMEOUT_SECONDS" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
    echo "--timeout must be a positive number" >&2
    exit 1
fi

for cmd in docker curl php awk; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "Required command not found: $cmd" >&2
        exit 1
    fi
done

if [[ ! -x "scripts/gateway/load_test.sh" ]]; then
    echo "Required script not executable: scripts/gateway/load_test.sh" >&2
    exit 1
fi

export WWWUSER="${WWWUSER:-1000}"
export WWWGROUP="${WWWGROUP:-1000}"

TMP_DIR="$(mktemp -d)"
RESULT_FAILURES=0
UPSTREAM_STOPPED=0
REQUEST_ID="${REQUEST_ID_PREFIX}$(date +%s)"

log() {
    echo "[incident] $*"
}

pass() {
    echo "[PASS] $*"
}

fail() {
    echo "[FAIL] $*"
    RESULT_FAILURES=$((RESULT_FAILURES + 1))
}

cleanup() {
    if [[ "$AUTO_RESTORE" -eq 1 ]] && [[ "$UPSTREAM_STOPPED" -eq 1 ]]; then
        log "Restoring upstream service: $UPSTREAM_SERVICE"
        docker compose start "$UPSTREAM_SERVICE" >/dev/null 2>&1 || true
    fi

    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

prometheus_query_value() {
    local expression="$1"
    local output_file="$TMP_DIR/prom_query.json"
    local status

    status="$(
        curl --silent --show-error \
          --output "$output_file" \
          --write-out "%{http_code}" \
          --get \
          --data-urlencode "query=$expression" \
          "$PROMETHEUS_URL/api/v1/query" || true
    )"

    if [[ "$status" != "200" ]]; then
        echo "0"
        return
    fi

    php -r '
        $d = json_decode(file_get_contents($argv[1]), true);
        if (!is_array($d) || ($d["status"] ?? "") !== "success") {
            echo "0";
            exit(0);
        }
        $value = $d["data"]["result"][0]["value"][1] ?? "0";
        if (!is_numeric((string) $value)) {
            echo "0";
            exit(0);
        }
        echo (string) $value;
    ' "$output_file"
}

is_greater_than() {
    awk -v current="$1" -v baseline="$2" 'BEGIN { exit !(current + 0 > baseline + 0) }'
}

counter_changed_with_reset_tolerance() {
    local current="$1"
    local baseline="$2"

    awk -v current="$current" -v baseline="$baseline" '
        BEGIN {
            c = current + 0
            b = baseline + 0

            if (c > b) {
                exit(0)
            }

            # Counter reset case (e.g. KrakenD container restarted):
            # if baseline was greater and current is now positive, we still
            # observed events in the new process lifetime.
            if (c < b && c > 0) {
                exit(0)
            }

            exit(1)
        }
    '
}

log "Incident request id: $REQUEST_ID"
baseline_5xx_counter="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*",http_response_status_code=~"5.."})')"
baseline_upstream_error_counter="$(prometheus_query_value 'sum(krakend_backend_duration_count{krakend_endpoint_route=~"/v1/.*",error="true"})')"
echo "[incident] baseline_5xx_counter=${baseline_5xx_counter}"
echo "[incident] baseline_upstream_error_counter=${baseline_upstream_error_counter}"

log "Stopping upstream service: $UPSTREAM_SERVICE"
docker compose stop "$UPSTREAM_SERVICE" >/dev/null
UPSTREAM_STOPPED=1
pass "Upstream service stopped"

status_incident_request="$(
    curl --silent --show-error \
      --output "$TMP_DIR/incident_request.out" \
      --write-out "%{http_code}" \
      --request GET \
      --url "$GATEWAY_URL" \
      --header "X-Request-Id: $REQUEST_ID" || true
)"

if [[ "$status_incident_request" =~ ^5[0-9][0-9]$ ]] || [[ "$status_incident_request" == "000" ]]; then
    pass "Gateway reflected upstream failure (status: $status_incident_request)"
else
    fail "Gateway did not reflect expected failure while upstream down (status: $status_incident_request)"
fi

log "Running controlled load against gateway..."
scripts/gateway/load_test.sh \
  --url "$GATEWAY_URL" \
  --requests "$REQUESTS" \
  --concurrency "$CONCURRENCY" \
  --timeout "$TIMEOUT_SECONDS" \
  --request-id-prefix "${REQUEST_ID}-load-" \
  --output "$TMP_DIR/load.raw" \
  > "$TMP_DIR/load.summary"

cat "$TMP_DIR/load.summary"

count_5xx="$(awk '/^5xx:/{print $2}' "$TMP_DIR/load.summary" | head -n 1)"
count_5xx="${count_5xx:-0}"
if [[ "$count_5xx" =~ ^[0-9]+$ ]] && [[ "$count_5xx" -gt 0 ]]; then
    pass "Load produced 5xx responses: $count_5xx"
else
    fail "Load did not produce 5xx responses"
fi

log "Waiting Prometheus scrape and checking metric deltas..."
current_5xx_counter="$baseline_5xx_counter"
current_upstream_error_counter="$baseline_upstream_error_counter"
metric_deadline=$(( $(date +%s) + METRIC_WAIT_SECONDS ))

while [[ "$(date +%s)" -le "$metric_deadline" ]]; do
    current_5xx_counter="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*",http_response_status_code=~"5.."})')"
    current_upstream_error_counter="$(prometheus_query_value 'sum(krakend_backend_duration_count{krakend_endpoint_route=~"/v1/.*",error="true"})')"

    if is_greater_than "$current_5xx_counter" "$baseline_5xx_counter" \
        && is_greater_than "$current_upstream_error_counter" "$baseline_upstream_error_counter"; then
        break
    fi

    sleep "$METRIC_POLL_INTERVAL"
done

echo "[incident] current_5xx_counter=${current_5xx_counter}"
echo "[incident] current_upstream_error_counter=${current_upstream_error_counter}"

if counter_changed_with_reset_tolerance "$current_5xx_counter" "$baseline_5xx_counter"; then
    pass "Prometheus observed gateway 5xx events (with counter reset tolerance)"
else
    fail "Prometheus did not observe gateway 5xx events"
fi

if counter_changed_with_reset_tolerance "$current_upstream_error_counter" "$baseline_upstream_error_counter"; then
    pass "Prometheus observed upstream error events (with counter reset tolerance)"
else
    fail "Prometheus did not observe upstream error events"
fi

if [[ "$AUTO_RESTORE" -eq 1 ]]; then
    log "Starting upstream service: $UPSTREAM_SERVICE"
    docker compose start "$UPSTREAM_SERVICE" >/dev/null
    UPSTREAM_STOPPED=0

    recovered=0
    for _ in $(seq 1 30); do
        status_recovery="$(
            curl --silent --show-error \
              --output "$TMP_DIR/recovery.out" \
              --write-out "%{http_code}" \
              "$APP_URL/dashboard/quotations" || true
        )"
        if [[ "$status_recovery" == "200" ]]; then
            recovered=1
            break
        fi
        sleep 2
    done

    if [[ "$recovered" -eq 1 ]]; then
        pass "Upstream recovered and direct app endpoint is healthy"
    else
        fail "Upstream recovery check failed"
    fi
fi

log "Checking Jaeger traces for request correlation..."
jaeger_trace_found=0
for _ in $(seq 1 30); do
    jaeger_status="$(
        curl --silent --show-error \
          --output "$TMP_DIR/jaeger_traces.json" \
          --write-out "%{http_code}" \
          --get \
          --data-urlencode "service=$JAEGER_SERVICE" \
          --data-urlencode "lookback=1h" \
          --data-urlencode "limit=100" \
          "$JAEGER_TRACES_URL" || true
    )"

    if [[ "$jaeger_status" == "200" ]] && grep -q "$REQUEST_ID" "$TMP_DIR/jaeger_traces.json"; then
        jaeger_trace_found=1
        break
    fi

    sleep 2
done

if [[ "$jaeger_trace_found" -eq 1 ]]; then
    pass "Jaeger trace correlation found for request id"
else
    fail "Jaeger trace correlation not found for request id"
fi

if [[ "$WAIT_ALERT_SECONDS" -gt 0 ]]; then
    log "Waiting for firing alerts in Prometheus (timeout: ${WAIT_ALERT_SECONDS}s)..."
    deadline_epoch=$(( $(date +%s) + WAIT_ALERT_SECONDS ))
    alerts_firing=0

    while [[ "$(date +%s)" -le "$deadline_epoch" ]]; do
        prom_alert_status="$(
            curl --silent --show-error \
              --output "$TMP_DIR/prom_alerts.json" \
              --write-out "%{http_code}" \
              "$PROMETHEUS_URL/api/v1/alerts" || true
        )"

        if [[ "$prom_alert_status" == "200" ]]; then
            if grep -q '"alertname":"KrakenDHigh5xxRate"' "$TMP_DIR/prom_alerts.json" \
                || grep -q '"alertname":"KrakenDUpstreamErrors"' "$TMP_DIR/prom_alerts.json"; then
                if grep -q '"state":"firing"' "$TMP_DIR/prom_alerts.json"; then
                    alerts_firing=1
                    break
                fi
            fi
        fi

        sleep "$ALERT_POLL_INTERVAL"
    done

    if [[ "$alerts_firing" -eq 1 ]]; then
        pass "Prometheus reported firing KrakenD incident alert(s)"
    else
        fail "Prometheus did not report firing alert(s) within timeout"
    fi
fi

echo
if [[ "$RESULT_FAILURES" -eq 0 ]]; then
    echo "[incident] RESULT: SUCCESS"
else
    echo "[incident] RESULT: FAILURE ($RESULT_FAILURES check(s) failed)"
fi

exit "$RESULT_FAILURES"
