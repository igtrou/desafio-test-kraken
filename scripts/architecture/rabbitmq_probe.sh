#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/architecture/rabbitmq_probe.sh [options]

Options:
  --api-url URL        RabbitMQ Management API base URL (default: http://localhost:15672/api)
  --user USER          RabbitMQ user (default: guest)
  --password PASS      RabbitMQ password (default: guest)
  --vhost VHOST        RabbitMQ vhost (default: /)
  --queue NAME         Queue name for probe (default: architecture.probe)
  --help               Show this help
EOF
}

RABBITMQ_API_URL="http://localhost:15672/api"
RABBITMQ_USER="guest"
RABBITMQ_PASSWORD="guest"
RABBITMQ_VHOST="/"
QUEUE_NAME="architecture.probe"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --api-url)
            RABBITMQ_API_URL="${2:-}"
            shift 2
            ;;
        --user)
            RABBITMQ_USER="${2:-}"
            shift 2
            ;;
        --password)
            RABBITMQ_PASSWORD="${2:-}"
            shift 2
            ;;
        --vhost)
            RABBITMQ_VHOST="${2:-}"
            shift 2
            ;;
        --queue)
            QUEUE_NAME="${2:-}"
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

urlencode() {
    php -r 'echo rawurlencode($argv[1]);' "$1"
}

VHOST_ENCODED="$(urlencode "$RABBITMQ_VHOST")"
TMP_DIR="$(mktemp -d)"
RUN_ID="probe-$(date +%s)"
PAYLOAD="architecture-rabbitmq-${RUN_ID}"

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

request() {
    local method="$1"
    local url="$2"
    local body="${3:-}"
    local out_file="$4"
    local status

    if [[ -n "$body" ]]; then
        status="$(
            curl --silent --show-error \
              --output "$out_file" \
              --write-out "%{http_code}" \
              --user "$RABBITMQ_USER:$RABBITMQ_PASSWORD" \
              --header 'Content-Type: application/json' \
              --request "$method" \
              --data "$body" \
              "$url" || true
        )"
    else
        status="$(
            curl --silent --show-error \
              --output "$out_file" \
              --write-out "%{http_code}" \
              --user "$RABBITMQ_USER:$RABBITMQ_PASSWORD" \
              --request "$method" \
              "$url" || true
        )"
    fi

    echo "$status"
}

queue_url="${RABBITMQ_API_URL}/queues/${VHOST_ENCODED}/${QUEUE_NAME}"
exchange_publish_url="${RABBITMQ_API_URL}/exchanges/${VHOST_ENCODED}/amq.default/publish"
queue_get_url="${RABBITMQ_API_URL}/queues/${VHOST_ENCODED}/${QUEUE_NAME}/get"

# 1) Declare queue
status_declare="$(
    request "PUT" "$queue_url" \
      '{"auto_delete":false,"durable":false,"arguments":{}}' \
      "$TMP_DIR/declare.json"
)"
if [[ "$status_declare" != "201" && "$status_declare" != "204" ]]; then
    echo "Queue declare failed (status: $status_declare)" >&2
    exit 1
fi

# 2) Publish probe message
publish_body="$(
    php -r '
      $payload = $argv[1];
      echo json_encode([
        "properties" => new stdClass(),
        "routing_key" => $argv[2],
        "payload" => $payload,
        "payload_encoding" => "string",
      ], JSON_UNESCAPED_SLASHES);
    ' "$PAYLOAD" "$QUEUE_NAME"
)"
status_publish="$(
    request "POST" "$exchange_publish_url" "$publish_body" "$TMP_DIR/publish.json"
)"
if [[ "$status_publish" != "200" ]]; then
    echo "Publish failed (status: $status_publish)" >&2
    exit 1
fi

publish_ok="$(
    php -r '
      $d = json_decode(file_get_contents($argv[1]), true);
      echo (is_array($d) && ($d["routed"] ?? false)) ? "1" : "0";
    ' "$TMP_DIR/publish.json"
)"
if [[ "$publish_ok" != "1" ]]; then
    echo "Publish response indicates not routed" >&2
    exit 1
fi

# 3) Consume probe message
status_get="$(
    request "POST" "$queue_get_url" \
      '{"count":1,"ackmode":"ack_requeue_false","encoding":"auto","truncate":50000}' \
      "$TMP_DIR/get.json"
)"
if [[ "$status_get" != "200" ]]; then
    echo "Queue get failed (status: $status_get)" >&2
    exit 1
fi

consumed_payload="$(
    php -r '
      $d = json_decode(file_get_contents($argv[1]), true);
      if (!is_array($d) || !isset($d[0]) || !is_array($d[0])) {
          echo "";
          exit(0);
      }
      echo (string) ($d[0]["payload"] ?? "");
    ' "$TMP_DIR/get.json"
)"

if [[ "$consumed_payload" != "$PAYLOAD" ]]; then
    echo "Consumed payload mismatch: expected '$PAYLOAD', got '$consumed_payload'" >&2
    exit 1
fi

echo "[rabbitmq-probe] SUCCESS queue=$QUEUE_NAME run_id=$RUN_ID"
