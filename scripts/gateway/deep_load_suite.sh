#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/gateway/deep_load_suite.sh [options]

Description:
  Runs a detailed gateway suite with:
  1) service probes
  2) auth checks (Sanctum and Keycloak when available)
  3) functional HTTP assertions
  4) multiple load scenarios
  5) Prometheus utilization snapshots (optional)

Options:
  --gateway-base-url URL            Gateway base URL (default: http://localhost:8080)
  --app-url URL                     Laravel direct URL (default: http://localhost)
  --prometheus-url URL              Prometheus base URL (default: http://localhost:9090)
  --keycloak-token-url URL          Keycloak token endpoint
                                    (default: http://localhost:8085/realms/krakend/protocol/openid-connect/token)
  --public-symbol VALUE             Symbol used in public scenarios (default: BTC)
  --private-symbol VALUE            Symbol used in private scenarios (default: BTC)
  --type VALUE                      Asset type query string (default: crypto)
  --report-dir PATH                 Report root directory
                                    (default: storage/app/operations/load-reports)

  --baseline-requests N             Baseline request count (default: 30)
  --baseline-concurrency N          Baseline concurrency (default: 2)
  --burst-requests N                Burst request count (default: 120)
  --burst-concurrency N             Burst concurrency (default: 8)
  --stress-requests N               Stress request count (default: 300)
  --stress-concurrency N            Stress concurrency (default: 20)
  --soak-rounds N                   Soak rounds (default: 3)
  --soak-requests N                 Soak requests per round (default: 120)
  --soak-concurrency N              Soak concurrency (default: 4)
  --timeout N                       Per-request timeout in seconds for load script (default: 6)
  --prometheus-settle-seconds N     Wait before final Prometheus snapshot (default: 20)
  --throttle-recovery-seconds N     Wait between retries when 429 is detected (default: 65)
  --throttle-recovery-attempts N    Retry attempts for 429 recovery checks (default: 2)

  --sanctum-email VALUE             Sanctum login email (default: test@example.com)
  --sanctum-password VALUE          Sanctum login password (default: password)
  --sanctum-device-name VALUE       Sanctum device name (default: deep-load-suite)
  --keycloak-client-id VALUE        Keycloak client id (default: krakend-playground)
  --keycloak-reader-user VALUE      Keycloak reader user (default: reader)
  --keycloak-reader-password VALUE  Keycloak reader password (default: reader)
  --keycloak-moderator-user VALUE   Keycloak moderator user (default: moderator)
  --keycloak-moderator-password VALUE
                                    Keycloak moderator password (default: moderator)

  --public-only                     Run only public scenarios and skip private auth checks
  --skip-prometheus                 Skip Prometheus metric snapshots
  --strict                          Treat optional failures (Keycloak/Prometheus) as failures
  --quick                           Reduce load values for a quick smoke run
  --help                            Show this help

Examples:
  scripts/gateway/deep_load_suite.sh
  scripts/gateway/deep_load_suite.sh --quick --public-only
  scripts/gateway/deep_load_suite.sh --strict --soak-rounds 5
EOF
}

GATEWAY_BASE_URL="${GATEWAY_BASE_URL:-http://localhost:8080}"
APP_URL="${APP_URL:-http://localhost}"
PROMETHEUS_URL="${PROMETHEUS_URL:-http://localhost:9090}"
KEYCLOAK_TOKEN_URL="${KEYCLOAK_TOKEN_URL:-http://localhost:8085/realms/krakend/protocol/openid-connect/token}"

PUBLIC_SYMBOL="${PUBLIC_SYMBOL:-BTC}"
PRIVATE_SYMBOL="${PRIVATE_SYMBOL:-BTC}"
QUOTE_TYPE="${QUOTE_TYPE:-crypto}"
REPORT_DIR="${REPORT_DIR:-storage/app/operations/load-reports}"

BASELINE_REQUESTS=30
BASELINE_CONCURRENCY=2
BURST_REQUESTS=120
BURST_CONCURRENCY=8
STRESS_REQUESTS=300
STRESS_CONCURRENCY=20
SOAK_ROUNDS=3
SOAK_REQUESTS=120
SOAK_CONCURRENCY=4
LOAD_TIMEOUT_SECONDS=6
PROMETHEUS_SETTLE_SECONDS=20
THROTTLE_RECOVERY_SECONDS=65
THROTTLE_RECOVERY_ATTEMPTS=2

SANCTUM_EMAIL="${SANCTUM_EMAIL:-test@example.com}"
SANCTUM_PASSWORD="${SANCTUM_PASSWORD:-password}"
SANCTUM_DEVICE_NAME="${SANCTUM_DEVICE_NAME:-deep-load-suite}"

KEYCLOAK_CLIENT_ID="${KEYCLOAK_CLIENT_ID:-krakend-playground}"
KEYCLOAK_READER_USER="${KEYCLOAK_READER_USER:-reader}"
KEYCLOAK_READER_PASSWORD="${KEYCLOAK_READER_PASSWORD:-reader}"
KEYCLOAK_MODERATOR_USER="${KEYCLOAK_MODERATOR_USER:-moderator}"
KEYCLOAK_MODERATOR_PASSWORD="${KEYCLOAK_MODERATOR_PASSWORD:-moderator}"

RUN_PRIVATE_SCENARIOS=1
RUN_PROMETHEUS_METRICS=1
STRICT_MODE=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --gateway-base-url)
            GATEWAY_BASE_URL="${2:-}"
            shift 2
            ;;
        --app-url)
            APP_URL="${2:-}"
            shift 2
            ;;
        --prometheus-url)
            PROMETHEUS_URL="${2:-}"
            shift 2
            ;;
        --keycloak-token-url)
            KEYCLOAK_TOKEN_URL="${2:-}"
            shift 2
            ;;
        --public-symbol)
            PUBLIC_SYMBOL="${2:-}"
            shift 2
            ;;
        --private-symbol)
            PRIVATE_SYMBOL="${2:-}"
            shift 2
            ;;
        --type)
            QUOTE_TYPE="${2:-}"
            shift 2
            ;;
        --report-dir)
            REPORT_DIR="${2:-}"
            shift 2
            ;;
        --baseline-requests)
            BASELINE_REQUESTS="${2:-}"
            shift 2
            ;;
        --baseline-concurrency)
            BASELINE_CONCURRENCY="${2:-}"
            shift 2
            ;;
        --burst-requests)
            BURST_REQUESTS="${2:-}"
            shift 2
            ;;
        --burst-concurrency)
            BURST_CONCURRENCY="${2:-}"
            shift 2
            ;;
        --stress-requests)
            STRESS_REQUESTS="${2:-}"
            shift 2
            ;;
        --stress-concurrency)
            STRESS_CONCURRENCY="${2:-}"
            shift 2
            ;;
        --soak-rounds)
            SOAK_ROUNDS="${2:-}"
            shift 2
            ;;
        --soak-requests)
            SOAK_REQUESTS="${2:-}"
            shift 2
            ;;
        --soak-concurrency)
            SOAK_CONCURRENCY="${2:-}"
            shift 2
            ;;
        --timeout)
            LOAD_TIMEOUT_SECONDS="${2:-}"
            shift 2
            ;;
        --prometheus-settle-seconds)
            PROMETHEUS_SETTLE_SECONDS="${2:-}"
            shift 2
            ;;
        --throttle-recovery-seconds)
            THROTTLE_RECOVERY_SECONDS="${2:-}"
            shift 2
            ;;
        --throttle-recovery-attempts)
            THROTTLE_RECOVERY_ATTEMPTS="${2:-}"
            shift 2
            ;;
        --sanctum-email)
            SANCTUM_EMAIL="${2:-}"
            shift 2
            ;;
        --sanctum-password)
            SANCTUM_PASSWORD="${2:-}"
            shift 2
            ;;
        --sanctum-device-name)
            SANCTUM_DEVICE_NAME="${2:-}"
            shift 2
            ;;
        --keycloak-client-id)
            KEYCLOAK_CLIENT_ID="${2:-}"
            shift 2
            ;;
        --keycloak-reader-user)
            KEYCLOAK_READER_USER="${2:-}"
            shift 2
            ;;
        --keycloak-reader-password)
            KEYCLOAK_READER_PASSWORD="${2:-}"
            shift 2
            ;;
        --keycloak-moderator-user)
            KEYCLOAK_MODERATOR_USER="${2:-}"
            shift 2
            ;;
        --keycloak-moderator-password)
            KEYCLOAK_MODERATOR_PASSWORD="${2:-}"
            shift 2
            ;;
        --public-only)
            RUN_PRIVATE_SCENARIOS=0
            shift
            ;;
        --skip-prometheus)
            RUN_PROMETHEUS_METRICS=0
            shift
            ;;
        --strict)
            STRICT_MODE=1
            shift
            ;;
        --quick)
            BASELINE_REQUESTS=10
            BASELINE_CONCURRENCY=2
            BURST_REQUESTS=30
            BURST_CONCURRENCY=4
            STRESS_REQUESTS=60
            STRESS_CONCURRENCY=6
            SOAK_ROUNDS=1
            SOAK_REQUESTS=30
            SOAK_CONCURRENCY=2
            LOAD_TIMEOUT_SECONDS=4
            PROMETHEUS_SETTLE_SECONDS=0
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

for value in \
    BASELINE_REQUESTS BASELINE_CONCURRENCY \
    BURST_REQUESTS BURST_CONCURRENCY \
    STRESS_REQUESTS STRESS_CONCURRENCY \
    SOAK_ROUNDS SOAK_REQUESTS SOAK_CONCURRENCY \
    PROMETHEUS_SETTLE_SECONDS \
    THROTTLE_RECOVERY_SECONDS \
    THROTTLE_RECOVERY_ATTEMPTS
do
    if ! [[ "${!value}" =~ ^[0-9]+$ ]] || [[ "${!value}" -le 0 ]]; then
        if [[ "$value" == "PROMETHEUS_SETTLE_SECONDS" ]] && [[ "${!value}" == "0" ]]; then
            continue
        fi
        echo "Invalid positive integer option: ${value}=${!value}" >&2
        exit 1
    fi
done

if ! [[ "$LOAD_TIMEOUT_SECONDS" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
    echo "Invalid timeout value: --timeout=${LOAD_TIMEOUT_SECONDS}" >&2
    exit 1
fi

for cmd in curl awk sort wc date mktemp xargs php sed; do
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

RUN_TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
RUN_DIR="${REPORT_DIR}/${RUN_TIMESTAMP}"
mkdir -p "$RUN_DIR"

TMP_DIR="$(mktemp -d)"
SERVICE_ROWS_FILE="$TMP_DIR/service_rows.txt"
FUNCTIONAL_ROWS_FILE="$TMP_DIR/functional_rows.txt"
LOAD_ROWS_FILE="$TMP_DIR/load_rows.txt"
PROM_ROWS_FILE="$TMP_DIR/prom_rows.txt"
touch "$SERVICE_ROWS_FILE" "$FUNCTIONAL_ROWS_FILE" "$LOAD_ROWS_FILE" "$PROM_ROWS_FILE"

FAILURES=0
WARNINGS=0
SKIPPED=0
TOTAL_CHECKS=0

SANCTUM_TOKEN=""
KEYCLOAK_READER_TOKEN=""
KEYCLOAK_MODERATOR_TOKEN=""
HAVE_PROMETHEUS=0
QUOTATION_WINDOW_STATUS=""

METRIC_TOTAL_REQ_BASELINE=""
METRIC_5XX_BASELINE=""
METRIC_429_BASELINE=""
METRIC_UPSTREAM_ERR_BASELINE=""
METRIC_P95_BASELINE=""

METRIC_TOTAL_REQ_FINAL=""
METRIC_5XX_FINAL=""
METRIC_429_FINAL=""
METRIC_UPSTREAM_ERR_FINAL=""
METRIC_P95_FINAL=""

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

log() {
    echo "[deep-load] $*"
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

sanitize_cell() {
    printf '%s' "$1" | tr '\n' ' ' | sed 's/|/\\|/g'
}

record_service_row() {
    printf '%s|%s|%s|%s\n' \
        "$(sanitize_cell "$1")" \
        "$(sanitize_cell "$2")" \
        "$(sanitize_cell "$3")" \
        "$(sanitize_cell "$4")" \
        >> "$SERVICE_ROWS_FILE"
}

record_functional_row() {
    printf '%s|%s|%s|%s|%s\n' \
        "$(sanitize_cell "$1")" \
        "$(sanitize_cell "$2")" \
        "$(sanitize_cell "$3")" \
        "$(sanitize_cell "$4")" \
        "$(sanitize_cell "$5")" \
        >> "$FUNCTIONAL_ROWS_FILE"
}

record_load_row() {
    printf '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s\n' \
        "$(sanitize_cell "$1")" \
        "$(sanitize_cell "$2")" \
        "$(sanitize_cell "$3")" \
        "$(sanitize_cell "$4")" \
        "$(sanitize_cell "$5")" \
        "$(sanitize_cell "$6")" \
        "$(sanitize_cell "$7")" \
        "$(sanitize_cell "$8")" \
        "$(sanitize_cell "$9")" \
        "$(sanitize_cell "${10}")" \
        "$(sanitize_cell "${11}")" \
        "$(sanitize_cell "${12}")" \
        "$(sanitize_cell "${13}")" \
        "$(sanitize_cell "${14}")" \
        "$(sanitize_cell "${15}")" \
        "$(sanitize_cell "${16}")" \
        >> "$LOAD_ROWS_FILE"
}

record_prom_row() {
    printf '%s|%s|%s|%s\n' \
        "$(sanitize_cell "$1")" \
        "$(sanitize_cell "$2")" \
        "$(sanitize_cell "$3")" \
        "$(sanitize_cell "$4")" \
        >> "$PROM_ROWS_FILE"
}

status_in_csv() {
    local status="$1"
    local expected_csv="$2"
    local expected
    IFS=',' read -r -a expected <<< "$expected_csv"
    for value in "${expected[@]}"; do
        if [[ "$status" == "$value" ]]; then
            return 0
        fi
    done
    return 1
}

safe_int() {
    local value="$1"
    if [[ "$value" =~ ^-?[0-9]+$ ]]; then
        printf '%s' "$value"
    else
        printf '0'
    fi
}

safe_float() {
    local value="$1"
    if [[ "$value" =~ ^-?[0-9]+([.][0-9]+)?([eE][-+]?[0-9]+)?$ ]]; then
        printf '%s' "$value"
    else
        printf '0'
    fi
}

float_delta() {
    local baseline="$1"
    local final="$2"
    awk -v a="$(safe_float "$baseline")" -v b="$(safe_float "$final")" 'BEGIN { printf "%.6f", (b + 0) - (a + 0) }'
}

probe_status() {
    local method="$1"
    local url="$2"
    local output_file="$3"
    shift 3

    local -a args=(
        --silent
        --show-error
        --output "$output_file"
        --write-out "%{http_code}"
        --request "$method"
    )

    while [[ $# -gt 0 ]]; do
        args+=(--header "$1")
        shift
    done

    args+=("$url")
    curl "${args[@]}" || true
}

probe_json_status() {
    local method="$1"
    local url="$2"
    local payload="$3"
    local output_file="$4"
    shift 4

    local -a args=(
        --silent
        --show-error
        --output "$output_file"
        --write-out "%{http_code}"
        --request "$method"
        --header "Content-Type: application/json"
    )

    while [[ $# -gt 0 ]]; do
        args+=(--header "$1")
        shift
    done

    args+=(--data "$payload" "$url")
    curl "${args[@]}" || true
}

wait_for_quotation_window() {
    local attempt=0
    local status=""

    while true; do
        status="$(probe_status "GET" "$PUBLIC_QUOTE_URL" "$RUN_DIR/service-gateway-public-window-${attempt}.out")"
        QUOTATION_WINDOW_STATUS="$status"

        if [[ "$status" != "429" ]]; then
            return 0
        fi

        if [[ "$attempt" -ge "$THROTTLE_RECOVERY_ATTEMPTS" ]]; then
            return 1
        fi

        warn "Gateway quote route is throttled (429). Waiting ${THROTTLE_RECOVERY_SECONDS}s before retry ($((attempt + 1))/${THROTTLE_RECOVERY_ATTEMPTS})."
        sleep "$THROTTLE_RECOVERY_SECONDS"
        attempt=$((attempt + 1))
    done
}

json_path_value() {
    local file="$1"
    local path="$2"

    php -r '
        $file = $argv[1];
        $path = $argv[2];
        $raw = @file_get_contents($file);
        if ($raw === false) {
            exit(0);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            exit(0);
        }
        $value = $data;
        foreach (explode(".", $path) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }
            exit(0);
        }
        if (is_string($value) || is_numeric($value)) {
            echo (string) $value;
        }
    ' "$file" "$path"
}

prometheus_query_value() {
    local expression="$1"
    local output_file="$TMP_DIR/prometheus-query.json"
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
        echo ""
        return 1
    fi

    local value
    value="$(
        php -r '
            $d = json_decode(file_get_contents($argv[1]), true);
            if (!is_array($d) || ($d["status"] ?? "") !== "success") {
                exit(0);
            }
            $value = $d["data"]["result"][0]["value"][1] ?? null;
            if ($value === null) {
                exit(0);
            }
            if (!is_numeric((string) $value)) {
                exit(0);
            }
            echo (string) $value;
        ' "$output_file"
    )"

    if [[ -z "$value" ]]; then
        echo ""
        return 1
    fi

    echo "$value"
}

warn_or_fail_optional() {
    local message="$1"
    if [[ "$STRICT_MODE" -eq 1 ]]; then
        fail "$message"
    else
        warn "$message"
    fi
}

run_functional_check() {
    local check_name="$1"
    local method="$2"
    local url="$3"
    local expected_codes="$4"
    local payload="$5"
    shift 5

    local output_file="$RUN_DIR/functional-${check_name}.json"
    local status

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if [[ -n "$payload" ]]; then
        status="$(probe_json_status "$method" "$url" "$payload" "$output_file" "$@")"
    else
        status="$(probe_status "$method" "$url" "$output_file" "$@")"
    fi

    if status_in_csv "$status" "$expected_codes"; then
        pass "Functional check passed: ${check_name} (status ${status})"
        record_functional_row "$check_name" "PASS" "$status" "$expected_codes" "$url"
    else
        fail "Functional check failed: ${check_name} (status ${status}, expected ${expected_codes})"
        record_functional_row "$check_name" "FAIL" "$status" "$expected_codes" "$url"
    fi
}

run_load_scenario() {
    local scenario_name="$1"
    local method="$2"
    local url="$3"
    local requests="$4"
    local concurrency="$5"
    local timeout_seconds="$6"
    local min_2xx="$7"
    local max_5xx="$8"
    local max_network="$9"
    local max_non_429_4xx="${10}"
    local allow_all_429="${11}"
    shift 11

    local -a headers=("$@")
    local summary_file="$RUN_DIR/load-${scenario_name}.summary.txt"
    local raw_file="$RUN_DIR/load-${scenario_name}.raw.txt"
    local request_prefix="${RUN_TIMESTAMP}-${scenario_name}-"
    local load_exit_code=0

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    log "Running load scenario: ${scenario_name}"

    local -a command=(
        scripts/gateway/load_test.sh
        --url "$url"
        --method "$method"
        --requests "$requests"
        --concurrency "$concurrency"
        --timeout "$timeout_seconds"
        --request-id-prefix "$request_prefix"
        --output "$raw_file"
    )

    local header
    for header in "${headers[@]}"; do
        command+=(--header "$header")
    done

    set +e
    "${command[@]}" > "$summary_file" 2>&1
    load_exit_code=$?
    set -e

    local count_2xx count_3xx count_4xx count_5xx count_net count_429 count_4xx_non429
    local p95 rps duration notes scenario_status
    count_2xx="$(safe_int "$(awk '/^2xx:/{print $2}' "$summary_file" | head -n 1)")"
    count_3xx="$(safe_int "$(awk '/^3xx:/{print $2}' "$summary_file" | head -n 1)")"
    count_4xx="$(safe_int "$(awk '/^4xx:/{print $2}' "$summary_file" | head -n 1)")"
    count_5xx="$(safe_int "$(awk '/^5xx:/{print $2}' "$summary_file" | head -n 1)")"
    count_net="$(safe_int "$(awk '/^Network\/timeout\(000\):/{print $2}' "$summary_file" | head -n 1)")"
    count_429="$(safe_int "$(awk '$1 == 429 {count++} END {print count + 0}' "$raw_file")")"
    count_4xx_non429=$((count_4xx - count_429))
    p95="$(safe_float "$(awk -F': ' '/^p95:/{print $2}' "$summary_file" | head -n 1)")"
    rps="$(safe_float "$(awk -F': ' '/^Throughput \(req\/s\):/{print $2}' "$summary_file" | head -n 1)")"
    duration="$(safe_float "$(awk -F': ' '/^Duration:/{print $2}' "$summary_file" | sed 's/s$//' | head -n 1)")"

    scenario_status="PASS"
    notes="min_2xx=${min_2xx}, max_5xx=${max_5xx}, max_000=${max_network}, max_4xx_non429=${max_non_429_4xx}, 429=${count_429}, 4xx_non429=${count_4xx_non429}"

    if [[ "$load_exit_code" -ne 0 ]]; then
        scenario_status="FAIL"
        notes="${notes}; load script exit=${load_exit_code}"
    fi

    if [[ "$count_5xx" -gt "$max_5xx" ]]; then
        scenario_status="FAIL"
        notes="${notes}; 5xx=${count_5xx}>${max_5xx}"
    fi

    if [[ "$count_net" -gt "$max_network" ]]; then
        scenario_status="FAIL"
        notes="${notes}; 000=${count_net}>${max_network}"
    fi

    if [[ "$count_4xx_non429" -gt "$max_non_429_4xx" ]]; then
        scenario_status="FAIL"
        notes="${notes}; 4xx_non429=${count_4xx_non429}>${max_non_429_4xx}"
    fi

    if [[ "$count_2xx" -lt "$min_2xx" ]]; then
        if [[ "$allow_all_429" -eq 1 ]] && [[ "$count_2xx" -eq 0 ]] && [[ "$count_429" -gt 0 ]] && [[ "$count_4xx_non429" -eq 0 ]]; then
            notes="${notes}; allowed_all_429=true"
            warn "Load scenario throttled with 429 only: ${scenario_name}"
        else
            scenario_status="FAIL"
            notes="${notes}; 2xx=${count_2xx}<${min_2xx}"
        fi
    fi

    if [[ "$scenario_status" == "PASS" ]]; then
        pass "Load scenario passed: ${scenario_name}"
    else
        fail "Load scenario failed: ${scenario_name}"
    fi

    record_load_row \
        "$scenario_name" "$scenario_status" "$method" "$url" "$requests" "$concurrency" \
        "$count_2xx" "$count_3xx" "$count_4xx" "$count_5xx" "$count_net" \
        "$p95" "$rps" "$duration" "$notes" "$summary_file"
}

log "Run directory: $RUN_DIR"

PUBLIC_QUOTE_URL="${GATEWAY_BASE_URL}/v1/public/quotation/${PUBLIC_SYMBOL}?type=${QUOTE_TYPE}"
PRIVATE_QUOTE_URL="${GATEWAY_BASE_URL}/v1/private/quotation/${PRIVATE_SYMBOL}?type=${QUOTE_TYPE}"
PRIVATE_QUOTATIONS_URL="${GATEWAY_BASE_URL}/v1/private/quotations?symbol=${PRIVATE_SYMBOL}&per_page=5"
PRIVATE_USER_URL="${GATEWAY_BASE_URL}/v1/private/user"
TOKEN_ISSUE_URL="${GATEWAY_BASE_URL}/v1/public/auth/token"
TOKEN_REVOKE_URL="${GATEWAY_BASE_URL}/v1/private/auth/token"

log "Step 1/6 - Service probes"

status_laravel_dashboard="$(probe_status "GET" "${APP_URL}/dashboard/quotations" "$RUN_DIR/service-laravel-dashboard.out")"
TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
if [[ "$status_laravel_dashboard" == "200" ]]; then
    pass "Laravel direct probe ok (${status_laravel_dashboard})"
    record_service_row "laravel_dashboard" "PASS" "$status_laravel_dashboard" "${APP_URL}/dashboard/quotations"
else
    fail "Laravel direct probe failed (${status_laravel_dashboard})"
    record_service_row "laravel_dashboard" "FAIL" "$status_laravel_dashboard" "${APP_URL}/dashboard/quotations"
fi

wait_for_quotation_window || true
status_gateway_public="$QUOTATION_WINDOW_STATUS"
TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
if [[ "$status_gateway_public" == "200" ]]; then
    pass "Gateway public probe ok (${status_gateway_public})"
    record_service_row "gateway_public_quote" "PASS" "$status_gateway_public" "$PUBLIC_QUOTE_URL"
elif [[ "$status_gateway_public" == "429" ]]; then
    warn_or_fail_optional "Gateway public probe stayed throttled after retries (${status_gateway_public})"
    record_service_row "gateway_public_quote" "WARN" "$status_gateway_public" "$PUBLIC_QUOTE_URL"
else
    fail "Gateway public probe failed (${status_gateway_public})"
    record_service_row "gateway_public_quote" "FAIL" "$status_gateway_public" "$PUBLIC_QUOTE_URL"
fi

if [[ "$RUN_PROMETHEUS_METRICS" -eq 1 ]]; then
    status_prometheus="$(probe_status "GET" "${PROMETHEUS_URL}/api/v1/status/buildinfo" "$RUN_DIR/service-prometheus-buildinfo.out")"
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    if [[ "$status_prometheus" == "200" ]]; then
        pass "Prometheus probe ok (${status_prometheus})"
        record_service_row "prometheus" "PASS" "$status_prometheus" "${PROMETHEUS_URL}/api/v1/status/buildinfo"
        HAVE_PROMETHEUS=1
    else
        warn_or_fail_optional "Prometheus probe unavailable (${status_prometheus}). Metrics snapshot will be skipped."
        record_service_row "prometheus" "WARN" "$status_prometheus" "Metrics skipped"
        HAVE_PROMETHEUS=0
    fi
else
    SKIPPED=$((SKIPPED + 1))
    record_service_row "prometheus" "SKIP" "-" "Disabled by --skip-prometheus"
fi

log "Step 2/6 - Auth setup"

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]]; then
    sanctum_payload="$(printf '{"email":"%s","password":"%s","device_name":"%s"}' "$SANCTUM_EMAIL" "$SANCTUM_PASSWORD" "$SANCTUM_DEVICE_NAME")"
    sanctum_attempt=0
    sanctum_status=""

    while true; do
        sanctum_status="$(probe_json_status "POST" "$TOKEN_ISSUE_URL" "$sanctum_payload" "$RUN_DIR/auth-sanctum-token.json")"

        if status_in_csv "$sanctum_status" "200,201"; then
            break
        fi

        if status_in_csv "$sanctum_status" "429,500" && [[ "$sanctum_attempt" -lt "$THROTTLE_RECOVERY_ATTEMPTS" ]]; then
            warn "Sanctum token issuance returned ${sanctum_status}. Waiting ${THROTTLE_RECOVERY_SECONDS}s before retry ($((sanctum_attempt + 1))/${THROTTLE_RECOVERY_ATTEMPTS})."
            sleep "$THROTTLE_RECOVERY_SECONDS"
            sanctum_attempt=$((sanctum_attempt + 1))
            continue
        fi

        break
    done

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if status_in_csv "$sanctum_status" "200,201"; then
        SANCTUM_TOKEN="$(json_path_value "$RUN_DIR/auth-sanctum-token.json" "data.token")"
        if [[ -n "$SANCTUM_TOKEN" ]]; then
            pass "Sanctum token issued successfully"
            record_service_row "sanctum_issue_token" "PASS" "$sanctum_status" "$TOKEN_ISSUE_URL"
        else
            fail "Sanctum token response did not include data.token"
            record_service_row "sanctum_issue_token" "FAIL" "$sanctum_status" "Missing data.token"
        fi
    else
        if status_in_csv "$sanctum_status" "429,500"; then
            warn_or_fail_optional "Sanctum token issue remains throttled/unavailable (status ${sanctum_status})"
            record_service_row "sanctum_issue_token" "WARN" "$sanctum_status" "$TOKEN_ISSUE_URL"
        else
            fail "Sanctum token issue failed (status ${sanctum_status})"
            record_service_row "sanctum_issue_token" "FAIL" "$sanctum_status" "$TOKEN_ISSUE_URL"
        fi
    fi

    if [[ -n "$SANCTUM_TOKEN" ]]; then
        pass "Sanctum token ready for private user checks"
    else
        warn "Private Sanctum checks will be skipped because no Sanctum token is available"
        SKIPPED=$((SKIPPED + 1))
    fi

    keycloak_reader_status="$(
        curl --silent --show-error \
            --output "$RUN_DIR/auth-keycloak-reader.json" \
            --write-out "%{http_code}" \
            --request POST \
            --url "$KEYCLOAK_TOKEN_URL" \
            --header 'Content-Type: application/x-www-form-urlencoded' \
            --data-urlencode "client_id=${KEYCLOAK_CLIENT_ID}" \
            --data-urlencode "username=${KEYCLOAK_READER_USER}" \
            --data-urlencode "password=${KEYCLOAK_READER_PASSWORD}" \
            --data-urlencode 'grant_type=password' || true
    )"
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if [[ "$keycloak_reader_status" == "200" ]]; then
        KEYCLOAK_READER_TOKEN="$(json_path_value "$RUN_DIR/auth-keycloak-reader.json" "access_token")"
        if [[ -n "$KEYCLOAK_READER_TOKEN" ]]; then
            pass "Keycloak reader token issued successfully"
            record_service_row "keycloak_reader_token" "PASS" "$keycloak_reader_status" "$KEYCLOAK_TOKEN_URL"
        else
            warn_or_fail_optional "Keycloak reader token response did not include access_token"
            record_service_row "keycloak_reader_token" "WARN" "$keycloak_reader_status" "Missing access_token"
        fi
    else
        warn_or_fail_optional "Keycloak reader token failed (status ${keycloak_reader_status}); private JWT checks will be skipped."
        record_service_row "keycloak_reader_token" "WARN" "$keycloak_reader_status" "$KEYCLOAK_TOKEN_URL"
    fi

    keycloak_moderator_status="$(
        curl --silent --show-error \
            --output "$RUN_DIR/auth-keycloak-moderator.json" \
            --write-out "%{http_code}" \
            --request POST \
            --url "$KEYCLOAK_TOKEN_URL" \
            --header 'Content-Type: application/x-www-form-urlencoded' \
            --data-urlencode "client_id=${KEYCLOAK_CLIENT_ID}" \
            --data-urlencode "username=${KEYCLOAK_MODERATOR_USER}" \
            --data-urlencode "password=${KEYCLOAK_MODERATOR_PASSWORD}" \
            --data-urlencode 'grant_type=password' || true
    )"
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if [[ "$keycloak_moderator_status" == "200" ]]; then
        KEYCLOAK_MODERATOR_TOKEN="$(json_path_value "$RUN_DIR/auth-keycloak-moderator.json" "access_token")"
        if [[ -n "$KEYCLOAK_MODERATOR_TOKEN" ]]; then
            pass "Keycloak moderator token issued successfully"
            record_service_row "keycloak_moderator_token" "PASS" "$keycloak_moderator_status" "$KEYCLOAK_TOKEN_URL"
        else
            warn_or_fail_optional "Keycloak moderator response did not include access_token"
            record_service_row "keycloak_moderator_token" "WARN" "$keycloak_moderator_status" "Missing access_token"
        fi
    else
        warn_or_fail_optional "Keycloak moderator token failed (status ${keycloak_moderator_status}); moderator checks will be skipped."
        record_service_row "keycloak_moderator_token" "WARN" "$keycloak_moderator_status" "$KEYCLOAK_TOKEN_URL"
    fi
else
    SKIPPED=$((SKIPPED + 1))
    record_service_row "private_auth_setup" "SKIP" "-" "Disabled by --public-only"
fi

log "Step 3/6 - Prometheus baseline snapshot"

if [[ "$HAVE_PROMETHEUS" -eq 1 ]]; then
    METRIC_TOTAL_REQ_BASELINE="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*"})' || true)"
    METRIC_5XX_BASELINE="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*",http_response_status_code=~"5.."})' || true)"
    METRIC_429_BASELINE="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*",http_response_status_code="429"})' || true)"
    METRIC_UPSTREAM_ERR_BASELINE="$(prometheus_query_value 'sum(krakend_backend_duration_count{krakend_endpoint_route=~"/v1/.*",error="true"})' || true)"
    METRIC_P95_BASELINE="$(prometheus_query_value 'histogram_quantile(0.95, sum(rate(http_server_duration_bucket{http_route=~"/v1/.*"}[2m])) by (le))' || true)"

    if [[ -n "$METRIC_TOTAL_REQ_BASELINE" ]]; then
        pass "Prometheus baseline captured"
    else
        warn_or_fail_optional "Prometheus query returned empty values; final metrics section may be partial."
    fi
else
    SKIPPED=$((SKIPPED + 1))
fi

log "Step 4/6 - Functional checks"

wait_for_quotation_window || true
functional_window_status="$QUOTATION_WINDOW_STATUS"
if [[ "$functional_window_status" == "429" ]]; then
    warn_or_fail_optional "Proceeding with functional checks while quotation route remains throttled."
fi

run_functional_check \
    "public_quote_ok" \
    "GET" \
    "$PUBLIC_QUOTE_URL" \
    "200" \
    ""

run_functional_check \
    "public_quote_invalid_provider" \
    "GET" \
    "${GATEWAY_BASE_URL}/v1/public/quotation/${PUBLIC_SYMBOL}?provider=invalid-provider&type=${QUOTE_TYPE}" \
    "422" \
    ""

run_functional_check \
    "private_user_without_token" \
    "GET" \
    "$PRIVATE_USER_URL" \
    "401" \
    ""

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]] && [[ -n "$SANCTUM_TOKEN" ]]; then
    run_functional_check \
        "private_user_with_sanctum_token" \
        "GET" \
        "$PRIVATE_USER_URL" \
        "200" \
        "" \
        "Authorization: Bearer ${SANCTUM_TOKEN}"
else
    SKIPPED=$((SKIPPED + 1))
    record_functional_row "private_user_with_sanctum_token" "SKIP" "-" "200" "Missing Sanctum token or --public-only"
fi

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]] && [[ -n "$KEYCLOAK_READER_TOKEN" ]]; then
    run_functional_check \
        "private_quote_with_reader_jwt" \
        "GET" \
        "$PRIVATE_QUOTE_URL" \
        "200" \
        "" \
        "Authorization: Bearer ${KEYCLOAK_READER_TOKEN}"

    run_functional_check \
        "private_post_with_reader_jwt_forbidden" \
        "POST" \
        "$PRIVATE_QUOTE_URL" \
        "403" \
        "" \
        "Authorization: Bearer ${KEYCLOAK_READER_TOKEN}"
else
    SKIPPED=$((SKIPPED + 1))
    record_functional_row "private_quote_with_reader_jwt" "SKIP" "-" "200" "Missing reader JWT or --public-only"
    record_functional_row "private_post_with_reader_jwt_forbidden" "SKIP" "-" "403" "Missing reader JWT or --public-only"
fi

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]] && [[ -n "$KEYCLOAK_MODERATOR_TOKEN" ]]; then
    run_functional_check \
        "private_post_with_moderator_jwt" \
        "POST" \
        "$PRIVATE_QUOTE_URL" \
        "200,201" \
        "" \
        "Authorization: Bearer ${KEYCLOAK_MODERATOR_TOKEN}"
else
    SKIPPED=$((SKIPPED + 1))
    record_functional_row "private_post_with_moderator_jwt" "SKIP" "-" "200,201" "Missing moderator JWT or --public-only"
fi

log "Step 5/6 - Load scenarios"

wait_for_quotation_window || true
load_window_status="$QUOTATION_WINDOW_STATUS"
if [[ "$load_window_status" == "429" ]]; then
    warn_or_fail_optional "Starting load scenarios while quotation route remains throttled."
fi

run_load_scenario \
    "public_baseline" \
    "GET" \
    "$PUBLIC_QUOTE_URL" \
    "$BASELINE_REQUESTS" \
    "$BASELINE_CONCURRENCY" \
    "$LOAD_TIMEOUT_SECONDS" \
    "$BASELINE_REQUESTS" \
    0 \
    0 \
    0 \
    0

run_load_scenario \
    "public_burst" \
    "GET" \
    "$PUBLIC_QUOTE_URL" \
    "$BURST_REQUESTS" \
    "$BURST_CONCURRENCY" \
    "$LOAD_TIMEOUT_SECONDS" \
    1 \
    0 \
    0 \
    0 \
    1

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]] && [[ -n "$KEYCLOAK_READER_TOKEN" ]]; then
    run_load_scenario \
        "private_quote_reader_burst" \
        "GET" \
        "$PRIVATE_QUOTE_URL" \
        "$BURST_REQUESTS" \
        "$BURST_CONCURRENCY" \
        "$LOAD_TIMEOUT_SECONDS" \
        1 \
        0 \
        0 \
        0 \
        1 \
        "Authorization: Bearer ${KEYCLOAK_READER_TOKEN}"

    run_load_scenario \
        "private_quotations_reader_baseline" \
        "GET" \
        "$PRIVATE_QUOTATIONS_URL" \
        "$BASELINE_REQUESTS" \
        "$BASELINE_CONCURRENCY" \
        "$LOAD_TIMEOUT_SECONDS" \
        1 \
        0 \
        0 \
        0 \
        1 \
        "Authorization: Bearer ${KEYCLOAK_READER_TOKEN}"
else
    SKIPPED=$((SKIPPED + 1))
    record_load_row \
        "private_quote_reader_burst" "SKIP" "GET" "$PRIVATE_QUOTE_URL" "$BURST_REQUESTS" "$BURST_CONCURRENCY" \
        "-" "-" "-" "-" "-" "-" "-" "-" "Missing reader JWT or --public-only" "-"
    record_load_row \
        "private_quotations_reader_baseline" "SKIP" "GET" "$PRIVATE_QUOTATIONS_URL" "$BASELINE_REQUESTS" "$BASELINE_CONCURRENCY" \
        "-" "-" "-" "-" "-" "-" "-" "-" "Missing reader JWT or --public-only" "-"
fi

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]] && [[ -n "$KEYCLOAK_MODERATOR_TOKEN" ]]; then
    run_load_scenario \
        "private_post_moderator_moderate" \
        "POST" \
        "$PRIVATE_QUOTE_URL" \
        "$BASELINE_REQUESTS" \
        "$BASELINE_CONCURRENCY" \
        "$LOAD_TIMEOUT_SECONDS" \
        1 \
        0 \
        0 \
        0 \
        1 \
        "Authorization: Bearer ${KEYCLOAK_MODERATOR_TOKEN}"
else
    SKIPPED=$((SKIPPED + 1))
    record_load_row \
        "private_post_moderator_moderate" "SKIP" "POST" "$PRIVATE_QUOTE_URL" "$BASELINE_REQUESTS" "$BASELINE_CONCURRENCY" \
        "-" "-" "-" "-" "-" "-" "-" "-" "Missing moderator JWT or --public-only" "-"
fi

round=1
while [[ "$round" -le "$SOAK_ROUNDS" ]]; do
    run_load_scenario \
        "public_soak_round_${round}" \
        "GET" \
        "$PUBLIC_QUOTE_URL" \
        "$SOAK_REQUESTS" \
        "$SOAK_CONCURRENCY" \
        "$LOAD_TIMEOUT_SECONDS" \
        1 \
        0 \
        0 \
        0 \
        1
    round=$((round + 1))
done

run_load_scenario \
    "public_stress" \
    "GET" \
    "$PUBLIC_QUOTE_URL" \
    "$STRESS_REQUESTS" \
    "$STRESS_CONCURRENCY" \
    "$LOAD_TIMEOUT_SECONDS" \
    1 \
    0 \
    0 \
    0 \
    1

if [[ "$RUN_PRIVATE_SCENARIOS" -eq 1 ]] && [[ -n "$SANCTUM_TOKEN" ]]; then
    run_functional_check \
        "private_revoke_sanctum_token" \
        "DELETE" \
        "$TOKEN_REVOKE_URL" \
        "200" \
        "" \
        "Authorization: Bearer ${SANCTUM_TOKEN}"
fi

log "Step 6/6 - Prometheus final snapshot"

if [[ "$HAVE_PROMETHEUS" -eq 1 ]]; then
    if [[ "$PROMETHEUS_SETTLE_SECONDS" -gt 0 ]]; then
        log "Waiting ${PROMETHEUS_SETTLE_SECONDS}s for Prometheus scrape to settle"
        sleep "$PROMETHEUS_SETTLE_SECONDS"
    fi

    METRIC_TOTAL_REQ_FINAL="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*"})' || true)"
    METRIC_5XX_FINAL="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*",http_response_status_code=~"5.."})' || true)"
    METRIC_429_FINAL="$(prometheus_query_value 'sum(http_server_duration_count{http_route=~"/v1/.*",http_response_status_code="429"})' || true)"
    METRIC_UPSTREAM_ERR_FINAL="$(prometheus_query_value 'sum(krakend_backend_duration_count{krakend_endpoint_route=~"/v1/.*",error="true"})' || true)"
    METRIC_P95_FINAL="$(prometheus_query_value 'histogram_quantile(0.95, sum(rate(http_server_duration_bucket{http_route=~"/v1/.*"}[2m])) by (le))' || true)"

    record_prom_row "gateway_total_requests" "$METRIC_TOTAL_REQ_BASELINE" "$METRIC_TOTAL_REQ_FINAL" "$(float_delta "$METRIC_TOTAL_REQ_BASELINE" "$METRIC_TOTAL_REQ_FINAL")"
    record_prom_row "gateway_5xx_requests" "$METRIC_5XX_BASELINE" "$METRIC_5XX_FINAL" "$(float_delta "$METRIC_5XX_BASELINE" "$METRIC_5XX_FINAL")"
    record_prom_row "gateway_429_requests" "$METRIC_429_BASELINE" "$METRIC_429_FINAL" "$(float_delta "$METRIC_429_BASELINE" "$METRIC_429_FINAL")"
    record_prom_row "gateway_upstream_errors" "$METRIC_UPSTREAM_ERR_BASELINE" "$METRIC_UPSTREAM_ERR_FINAL" "$(float_delta "$METRIC_UPSTREAM_ERR_BASELINE" "$METRIC_UPSTREAM_ERR_FINAL")"
    record_prom_row "gateway_p95_seconds" "$METRIC_P95_BASELINE" "$METRIC_P95_FINAL" "$(float_delta "$METRIC_P95_BASELINE" "$METRIC_P95_FINAL")"
else
    record_prom_row "prometheus_metrics" "-" "-" "skipped"
fi

REPORT_FILE="$RUN_DIR/deep-load-report.md"
{
    echo "# Deep Gateway Load Suite Report"
    echo
    echo "- Timestamp (UTC): ${RUN_TIMESTAMP}"
    echo "- Gateway base URL: ${GATEWAY_BASE_URL}"
    echo "- Public symbol: ${PUBLIC_SYMBOL}"
    echo "- Private symbol: ${PRIVATE_SYMBOL}"
    echo "- Type: ${QUOTE_TYPE}"
    echo "- Total checks: ${TOTAL_CHECKS}"
    echo "- Failures: ${FAILURES}"
    echo "- Warnings: ${WARNINGS}"
    echo "- Skipped blocks: ${SKIPPED}"
    echo "- Strict mode: $([[ "$STRICT_MODE" -eq 1 ]] && echo yes || echo no)"
    echo
    echo "## Service Probes"
    echo
    echo "| Check | Status | HTTP | Notes |"
    echo "| --- | --- | --- | --- |"
    while IFS='|' read -r check_name status_code http_status notes; do
        printf '| `%s` | `%s` | `%s` | `%s` |\n' "$check_name" "$status_code" "$http_status" "$notes"
    done < "$SERVICE_ROWS_FILE"
    echo
    echo "## Functional Checks"
    echo
    echo "| Check | Status | HTTP | Expected | URL/Notes |"
    echo "| --- | --- | --- | --- | --- |"
    while IFS='|' read -r check_name status_code http_status expected_codes notes; do
        printf '| `%s` | `%s` | `%s` | `%s` | `%s` |\n' "$check_name" "$status_code" "$http_status" "$expected_codes" "$notes"
    done < "$FUNCTIONAL_ROWS_FILE"
    echo
    echo "## Load Scenarios"
    echo
    echo "| Scenario | Status | Method | Requests | Concurrency | 2xx | 3xx | 4xx | 5xx | 000 | p95(s) | req/s | Duration(s) | Notes | Summary file |"
    echo "| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |"
    while IFS='|' read -r name status_code method url req conc c2 c3 c4 c5 c000 p95 rps dur notes summary_file; do
        printf '| `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` |\n' \
            "$name" "$status_code" "$method" "$req" "$conc" "$c2" "$c3" "$c4" "$c5" "$c000" "$p95" "$rps" "$dur" "$notes" "$summary_file"
    done < "$LOAD_ROWS_FILE"
    echo
    echo "## Prometheus Utilization Snapshot"
    echo
    echo "| Metric | Baseline | Final | Delta |"
    echo "| --- | --- | --- | --- |"
    while IFS='|' read -r metric_name baseline_value final_value delta_value; do
        printf '| `%s` | `%s` | `%s` | `%s` |\n' "$metric_name" "$baseline_value" "$final_value" "$delta_value"
    done < "$PROM_ROWS_FILE"
} > "$REPORT_FILE"

if [[ "$FAILURES" -eq 0 ]]; then
    pass "Suite completed successfully"
else
    echo "[FAIL] Suite completed with failures"
fi

echo "[deep-load] Report: ${REPORT_FILE}"
echo "[deep-load] Artifacts dir: ${RUN_DIR}"

if [[ "$FAILURES" -gt 0 ]]; then
    exit 1
fi

exit 0
