#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/architecture/smoke_all_services.sh [options]

Options:
  --up                    Start full stack before running checks
  --down                  Stop full stack after checks (even on failure)
  --max-attempts N        Retry attempts for wait checks (default: 30)
  --sleep-seconds N       Wait interval between retries (default: 2)
  --help                  Show this help

Environment overrides:
  APP_URL                 Default: http://localhost
  KRAKEND_URL             Default: http://localhost:8080
  KRAKEND_DEBUG_URL       Default: http://localhost:8090
  KRAKEND_METRICS_URL     Default: http://localhost:9091/metrics
  KEYCLOAK_TOKEN_URL      Default: http://localhost:8085/realms/krakend/protocol/openid-connect/token
  RABBITMQ_OVERVIEW_URL   Default: http://localhost:15672/api/overview
  RABBITMQ_API_URL        Default: RABBITMQ_OVERVIEW_URL without '/overview'
  RABBITMQ_PROBE_QUEUE    Default: architecture.probe
  PROMETHEUS_URL          Default: http://localhost:9090
  JAEGER_SERVICES_URL     Default: http://localhost:16686/api/services
  INFLUX_PING_URL         Default: http://localhost:8086/ping
  INFLUX_URL              Default: http://localhost:8086
  INFLUX_PROBE_DB         Default: architecture_probe
  INFLUX_PROBE_MEASUREMENT Default: service_probe
  GRAFANA_HEALTH_URL      Default: http://localhost:4000/api/health
  KEYCLOAK_CLIENT_ID      Default: krakend-playground
  KEYCLOAK_USER           Default: reader
  KEYCLOAK_PASSWORD       Default: reader
  RABBITMQ_USER           Default: guest
  RABBITMQ_PASSWORD       Default: guest
  GRAFANA_USER            Default: admin
  GRAFANA_PASSWORD        Default: admin
EOF
}

UP_STACK=0
DOWN_STACK=0
MAX_ATTEMPTS=30
SLEEP_SECONDS=2

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
        --max-attempts)
            MAX_ATTEMPTS="${2:-}"
            shift 2
            ;;
        --sleep-seconds)
            SLEEP_SECONDS="${2:-}"
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

if ! [[ "$MAX_ATTEMPTS" =~ ^[0-9]+$ ]] || [[ "$MAX_ATTEMPTS" -le 0 ]]; then
    echo "--max-attempts must be a positive integer" >&2
    exit 1
fi

if ! [[ "$SLEEP_SECONDS" =~ ^[0-9]+$ ]] || [[ "$SLEEP_SECONDS" -le 0 ]]; then
    echo "--sleep-seconds must be a positive integer" >&2
    exit 1
fi

APP_URL="${APP_URL:-http://localhost}"
KRAKEND_URL="${KRAKEND_URL:-http://localhost:8080}"
KRAKEND_DEBUG_URL="${KRAKEND_DEBUG_URL:-http://localhost:8090}"
KRAKEND_METRICS_URL="${KRAKEND_METRICS_URL:-http://localhost:9091/metrics}"
KEYCLOAK_TOKEN_URL="${KEYCLOAK_TOKEN_URL:-http://localhost:8085/realms/krakend/protocol/openid-connect/token}"
RABBITMQ_OVERVIEW_URL="${RABBITMQ_OVERVIEW_URL:-http://localhost:15672/api/overview}"
RABBITMQ_API_URL="${RABBITMQ_API_URL:-${RABBITMQ_OVERVIEW_URL%/overview}}"
RABBITMQ_PROBE_QUEUE="${RABBITMQ_PROBE_QUEUE:-architecture.probe}"
PROMETHEUS_URL="${PROMETHEUS_URL:-http://localhost:9090}"
JAEGER_SERVICES_URL="${JAEGER_SERVICES_URL:-http://localhost:16686/api/services}"
INFLUX_PING_URL="${INFLUX_PING_URL:-http://localhost:8086/ping}"
INFLUX_URL="${INFLUX_URL:-http://localhost:8086}"
INFLUX_PROBE_DB="${INFLUX_PROBE_DB:-architecture_probe}"
INFLUX_PROBE_MEASUREMENT="${INFLUX_PROBE_MEASUREMENT:-service_probe}"
GRAFANA_HEALTH_URL="${GRAFANA_HEALTH_URL:-http://localhost:4000/api/health}"

KEYCLOAK_CLIENT_ID="${KEYCLOAK_CLIENT_ID:-krakend-playground}"
KEYCLOAK_USER="${KEYCLOAK_USER:-reader}"
KEYCLOAK_PASSWORD="${KEYCLOAK_PASSWORD:-reader}"
RABBITMQ_USER="${RABBITMQ_USER:-guest}"
RABBITMQ_PASSWORD="${RABBITMQ_PASSWORD:-guest}"
GRAFANA_USER="${GRAFANA_USER:-admin}"
GRAFANA_PASSWORD="${GRAFANA_PASSWORD:-admin}"

TMP_DIR="$(mktemp -d)"
FAILURES=0
WARNINGS=0

cleanup() {
    if [[ "$DOWN_STACK" -eq 1 ]]; then
        echo "[arch-smoke] Stopping full stack..."
        scripts/architecture/up_full_stack.sh down --remove-orphans >/dev/null 2>&1 || true
    fi

    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

log() {
    echo "[arch-smoke] $*"
}

pass() {
    echo "[PASS] $*"
}

warn() {
    echo "[WARN] $*"
    WARNINGS=$((WARNINGS + 1))
}

fail() {
    echo "[FAIL] $*"
    FAILURES=$((FAILURES + 1))
}

status_in() {
    local status="$1"
    shift

    local expected
    for expected in "$@"; do
        if [[ "$status" == "$expected" ]]; then
            return 0
        fi
    done

    return 1
}

wait_for_running_service() {
    local service="$1"
    local attempt=1

    while [[ "$attempt" -le "$MAX_ATTEMPTS" ]]; do
        if docker compose ps --status running --services | grep -Fxq "$service"; then
            pass "Service running: $service"
            return 0
        fi

        sleep "$SLEEP_SECONDS"
        attempt=$((attempt + 1))
    done

    fail "Service not running after retries: $service"
    return 1
}

for cmd in docker curl php; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "Required command not found: $cmd" >&2
        exit 1
    fi
done

export WWWUSER="${WWWUSER:-1000}"
export WWWGROUP="${WWWGROUP:-1000}"

if [[ "$UP_STACK" -eq 1 ]]; then
    log "Starting full stack..."
    scripts/architecture/up_full_stack.sh up >/dev/null
fi

log "Waiting for services to be running..."
wait_for_running_service "laravel.test"
wait_for_running_service "mysql"
wait_for_running_service "redis"
wait_for_running_service "krakend"
wait_for_running_service "keycloak"
wait_for_running_service "rabbitmq"
wait_for_running_service "jaeger"
wait_for_running_service "influxdb"
wait_for_running_service "prometheus"
wait_for_running_service "grafana"

log "Checking MySQL and Redis..."
if docker compose exec -T mysql sh -lc 'mysqladmin ping -h 127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" >/dev/null 2>&1'; then
    pass "MySQL healthcheck command succeeded"
else
    fail "MySQL healthcheck command failed"
fi

redis_ping="$(docker compose exec -T redis redis-cli ping 2>/dev/null | tr -d '\r')"
if [[ "$redis_ping" == "PONG" ]]; then
    pass "Redis responded with PONG"
else
    fail "Redis ping failed (response: ${redis_ping:-<empty>})"
fi

log "Checking direct Laravel and KrakenD gateway..."
status_dashboard="$(
    curl --silent --show-error --output "$TMP_DIR/laravel_dashboard.out" --write-out "%{http_code}" \
      "$APP_URL/dashboard/quotations" || true
)"
if status_in "$status_dashboard" 200; then
    pass "Laravel dashboard route reachable (200)"
else
    fail "Laravel dashboard route failed (status: $status_dashboard)"
fi

status_gateway_api_probe="$(
    curl --silent --show-error --output "$TMP_DIR/krakend_api_probe.out" --write-out "%{http_code}" \
      "$KRAKEND_URL/v1/private/user" || true
)"
if status_in "$status_gateway_api_probe" 401; then
    pass "KrakenD API surface reachable at /v1/private/user (401 expected without token)"
else
    fail "KrakenD API probe failed (status: $status_gateway_api_probe)"
fi

status_debug="$(
    curl --silent --show-error --output "$TMP_DIR/krakend_debug.out" --write-out "%{http_code}" \
      "$KRAKEND_DEBUG_URL/" || true
)"
if ! status_in "$status_debug" 000; then
    pass "KrakenD debug port is reachable (status: $status_debug)"
else
    warn "KrakenD debug port is unreachable in this environment (non-blocking)"
fi

status_metrics="$(
    curl --silent --show-error --output "$TMP_DIR/krakend_metrics.out" --write-out "%{http_code}" \
      "$KRAKEND_METRICS_URL" || true
)"
if status_in "$status_metrics" 200 && grep -q "http_server_duration" "$TMP_DIR/krakend_metrics.out"; then
    pass "KrakenD metrics exporter reachable with expected metrics"
else
    fail "KrakenD metrics check failed (status: $status_metrics)"
fi

log "Checking Keycloak token flow and private route..."
status_keycloak_token="$(
    curl --silent --show-error \
      --output "$TMP_DIR/keycloak_token.out" \
      --write-out "%{http_code}" \
      --request POST \
      --url "$KEYCLOAK_TOKEN_URL" \
      --header 'Content-Type: application/x-www-form-urlencoded' \
      --data-urlencode "client_id=$KEYCLOAK_CLIENT_ID" \
      --data-urlencode "username=$KEYCLOAK_USER" \
      --data-urlencode "password=$KEYCLOAK_PASSWORD" \
      --data-urlencode 'grant_type=password' || true
)"

keycloak_access_token=""
if status_in "$status_keycloak_token" 200; then
    keycloak_access_token="$(
        php -r '$d=json_decode(file_get_contents($argv[1]), true); echo is_array($d) ? ($d["access_token"] ?? "") : "";' \
          "$TMP_DIR/keycloak_token.out"
    )"
fi

if [[ -n "$keycloak_access_token" ]]; then
    pass "Keycloak issued JWT access token"
else
    fail "Keycloak token request failed (status: $status_keycloak_token)"
fi

status_private_route="$(
    curl --silent --show-error \
      --output "$TMP_DIR/private_route.out" \
      --write-out "%{http_code}" \
      --request GET \
      --url "$KRAKEND_URL/v1/private/quotations?per_page=1" \
      --header "Authorization: Bearer $keycloak_access_token" || true
)"
if status_in "$status_private_route" 200; then
    pass "Private JWT route via KrakenD is reachable (200)"
else
    fail "Private JWT route failed (status: $status_private_route)"
fi

log "Checking RabbitMQ management API..."
status_rabbitmq="$(
    curl --silent --show-error \
      --output "$TMP_DIR/rabbitmq_overview.out" \
      --write-out "%{http_code}" \
      --user "$RABBITMQ_USER:$RABBITMQ_PASSWORD" \
      "$RABBITMQ_OVERVIEW_URL" || true
)"
if status_in "$status_rabbitmq" 200; then
    pass "RabbitMQ management API reachable (200)"
else
    fail "RabbitMQ management API failed (status: $status_rabbitmq)"
fi

if [[ -x "scripts/architecture/rabbitmq_probe.sh" ]]; then
    rabbitmq_probe_out="$TMP_DIR/rabbitmq_probe.out"
    if scripts/architecture/rabbitmq_probe.sh \
        --api-url "$RABBITMQ_API_URL" \
        --user "$RABBITMQ_USER" \
        --password "$RABBITMQ_PASSWORD" \
        --queue "$RABBITMQ_PROBE_QUEUE" \
        >"$rabbitmq_probe_out" 2>&1; then
        pass "RabbitMQ publish/consume probe succeeded"
    else
        fail "RabbitMQ publish/consume probe failed"
        cat "$rabbitmq_probe_out" || true
    fi
else
    warn "RabbitMQ probe script not found or not executable"
fi

log "Checking Prometheus and alert rules..."
status_prom_targets="$(
    curl --silent --show-error \
      --output "$TMP_DIR/prometheus_targets.out" \
      --write-out "%{http_code}" \
      "$PROMETHEUS_URL/api/v1/targets" || true
)"
if status_in "$status_prom_targets" 200 \
    && grep -q "krakend:9091" "$TMP_DIR/prometheus_targets.out"; then
    pass "Prometheus targets include krakend:9091"
else
    fail "Prometheus targets check failed (status: $status_prom_targets)"
fi

status_prom_rules="$(
    curl --silent --show-error \
      --output "$TMP_DIR/prometheus_rules.out" \
      --write-out "%{http_code}" \
      "$PROMETHEUS_URL/api/v1/rules" || true
)"
if status_in "$status_prom_rules" 200 \
    && grep -q "KrakenDHigh5xxRate" "$TMP_DIR/prometheus_rules.out" \
    && grep -q "KrakenDHighP95Latency" "$TMP_DIR/prometheus_rules.out" \
    && grep -q "KrakenDUpstreamErrors" "$TMP_DIR/prometheus_rules.out"; then
    pass "Prometheus loaded KrakenD alert rules"
else
    fail "Prometheus alert rules check failed (status: $status_prom_rules)"
fi

log "Checking Grafana and InfluxDB..."
status_grafana="$(
    curl --silent --show-error \
      --output "$TMP_DIR/grafana_health.out" \
      --write-out "%{http_code}" \
      --user "$GRAFANA_USER:$GRAFANA_PASSWORD" \
      "$GRAFANA_HEALTH_URL" || true
)"
if status_in "$status_grafana" 200 \
    && grep -Eq "\"database\"[[:space:]]*:[[:space:]]*\"ok\"" "$TMP_DIR/grafana_health.out"; then
    pass "Grafana health endpoint is healthy"
else
    fail "Grafana health check failed (status: $status_grafana)"
fi

status_influx="$(
    curl --silent --show-error \
      --output "$TMP_DIR/influx_ping.out" \
      --write-out "%{http_code}" \
      "$INFLUX_PING_URL" || true
)"
if status_in "$status_influx" 204 200; then
    pass "InfluxDB ping endpoint responded (status: $status_influx)"
else
    fail "InfluxDB ping check failed (status: $status_influx)"
fi

if [[ -x "scripts/architecture/influx_probe.sh" ]]; then
    influx_probe_out="$TMP_DIR/influx_probe.out"
    if scripts/architecture/influx_probe.sh \
        --url "$INFLUX_URL" \
        --db "$INFLUX_PROBE_DB" \
        --measurement "$INFLUX_PROBE_MEASUREMENT" \
        >"$influx_probe_out" 2>&1; then
        pass "InfluxDB write/read probe succeeded"
    else
        fail "InfluxDB write/read probe failed"
        cat "$influx_probe_out" || true
    fi
else
    warn "Influx probe script not found or not executable"
fi

log "Checking Jaeger for krakend_gateway traces..."
jaeger_found=0
for ((attempt = 1; attempt <= MAX_ATTEMPTS; attempt++)); do
    status_jaeger="$(
        curl --silent --show-error \
          --output "$TMP_DIR/jaeger_services.out" \
          --write-out "%{http_code}" \
          "$JAEGER_SERVICES_URL" || true
    )"

    if status_in "$status_jaeger" 200 && grep -q "krakend_gateway" "$TMP_DIR/jaeger_services.out"; then
        jaeger_found=1
        break
    fi

    sleep "$SLEEP_SECONDS"
done

if [[ "$jaeger_found" -eq 1 ]]; then
    pass "Jaeger contains krakend_gateway service"
else
    fail "Jaeger did not expose krakend_gateway after retries"
fi

echo
if [[ "$FAILURES" -eq 0 ]]; then
    echo "[arch-smoke] RESULT: SUCCESS (all blocking checks passed)"
    if [[ "$WARNINGS" -gt 0 ]]; then
        echo "[arch-smoke] WARNINGS: $WARNINGS"
    fi
else
    echo "[arch-smoke] RESULT: FAILURE ($FAILURES check(s) failed)"
    if [[ "$WARNINGS" -gt 0 ]]; then
        echo "[arch-smoke] WARNINGS: $WARNINGS"
    fi
fi

exit "$FAILURES"
