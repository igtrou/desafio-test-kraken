#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/architecture/influx_probe.sh [options]

Options:
  --url URL            InfluxDB base URL (default: http://localhost:8086)
  --db NAME            Database name for probe data (default: architecture_probe)
  --measurement NAME   Measurement name (default: service_probe)
  --help               Show this help
EOF
}

INFLUX_URL="http://localhost:8086"
INFLUX_DB="architecture_probe"
INFLUX_MEASUREMENT="service_probe"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --url)
            INFLUX_URL="${2:-}"
            shift 2
            ;;
        --db)
            INFLUX_DB="${2:-}"
            shift 2
            ;;
        --measurement)
            INFLUX_MEASUREMENT="${2:-}"
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

for cmd in curl php; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "Required command not found: $cmd" >&2
        exit 1
    fi
done

TMP_DIR="$(mktemp -d)"
RUN_ID="$(date +%s)"
POINT="${INFLUX_MEASUREMENT},service=architecture ok=1i,run_id=${RUN_ID}i"

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

# 1) Ensure database exists
status_create_db="$(
    curl --silent --show-error \
      --output "$TMP_DIR/create_db.json" \
      --write-out "%{http_code}" \
      --request POST \
      --data-urlencode "q=CREATE DATABASE \"$INFLUX_DB\"" \
      "$INFLUX_URL/query" || true
)"
if [[ "$status_create_db" != "200" ]]; then
    echo "Influx CREATE DATABASE failed (status: $status_create_db)" >&2
    exit 1
fi

# 2) Write probe point
status_write="$(
    curl --silent --show-error \
      --output "$TMP_DIR/write.out" \
      --write-out "%{http_code}" \
      --request POST \
      --data-binary "$POINT" \
      "$INFLUX_URL/write?db=$INFLUX_DB" || true
)"
if [[ "$status_write" != "204" ]]; then
    echo "Influx write failed (status: $status_write)" >&2
    exit 1
fi

# 3) Query probe point
status_query="$(
    curl --silent --show-error \
      --output "$TMP_DIR/query.json" \
      --write-out "%{http_code}" \
      --get \
      --data-urlencode "db=$INFLUX_DB" \
      --data-urlencode "q=SELECT ok,run_id FROM $INFLUX_MEASUREMENT ORDER BY time DESC LIMIT 1" \
      "$INFLUX_URL/query" || true
)"
if [[ "$status_query" != "200" ]]; then
    echo "Influx query failed (status: $status_query)" >&2
    exit 1
fi

queried_run_id="$(
    php -r '
      $d = json_decode(file_get_contents($argv[1]), true);
      if (!is_array($d)) {
          echo "";
          exit(0);
      }
      $series = $d["results"][0]["series"][0] ?? null;
      if (!is_array($series)) {
          echo "";
          exit(0);
      }
      $columns = $series["columns"] ?? [];
      $values = $series["values"][0] ?? [];
      if (!is_array($columns) || !is_array($values)) {
          echo "";
          exit(0);
      }
      $idx = array_search("run_id", $columns, true);
      if ($idx === false || !array_key_exists($idx, $values)) {
          echo "";
          exit(0);
      }
      echo (string) $values[$idx];
    ' "$TMP_DIR/query.json"
)"

if [[ "$queried_run_id" != "$RUN_ID" ]]; then
    echo "Influx queried run_id mismatch: expected '$RUN_ID', got '$queried_run_id'" >&2
    exit 1
fi

echo "[influx-probe] SUCCESS db=$INFLUX_DB measurement=$INFLUX_MEASUREMENT run_id=$RUN_ID"
