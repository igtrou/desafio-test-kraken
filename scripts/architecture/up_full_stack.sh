#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/architecture/up_full_stack.sh [command] [extra args...]

Commands:
  up      Start full architecture stack with all profiles (default)
  down    Stop and remove full architecture stack
  ps      Show stack status
  logs    Show stack logs (pass service name as extra arg if needed)

Examples:
  scripts/architecture/up_full_stack.sh
  scripts/architecture/up_full_stack.sh up -d
  scripts/architecture/up_full_stack.sh logs krakend
  scripts/architecture/up_full_stack.sh down --remove-orphans
EOF
}

compose_with_profiles() {
    export WWWUSER="${WWWUSER:-1000}"
    export WWWGROUP="${WWWGROUP:-1000}"

    docker compose \
      --profile krakend \
      --profile krakend-auth \
      --profile krakend-async \
      --profile krakend-observability \
      "$@"
}

command_name="${1:-up}"
if [[ $# -gt 0 ]]; then
    shift
fi

case "$command_name" in
    up)
        compose_with_profiles up -d "$@"
        ;;
    down)
        compose_with_profiles down "$@"
        ;;
    ps)
        compose_with_profiles ps "$@"
        ;;
    logs)
        compose_with_profiles logs -f "$@"
        ;;
    help|-h|--help)
        usage
        ;;
    *)
        echo "Unknown command: $command_name" >&2
        usage
        exit 1
        ;;
esac
