#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  scripts/gateway/load_test.sh --url URL [options]

Options:
  --url URL                  Target URL (required)
  --method METHOD            HTTP method (default: GET)
  --requests N               Total requests (default: 300)
  --concurrency N            Max concurrent workers (default: 20)
  --timeout SECONDS          Curl max-time per request (default: 6)
  --header "Name: value"     Additional request header (repeatable)
  --request-id-prefix VALUE  Prefix for X-Request-Id (default: loadtest-)
  --no-request-id            Do not send X-Request-Id
  --output FILE              Optional file to save raw "status latency" rows
  --help                     Show this help
EOF
}

url=""
method="GET"
requests=300
concurrency=20
timeout_seconds=6
request_id_prefix="loadtest-"
send_request_id=1
output_file=""
declare -a headers=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --url)
            url="${2:-}"
            shift 2
            ;;
        --method)
            method="${2:-}"
            shift 2
            ;;
        --requests)
            requests="${2:-}"
            shift 2
            ;;
        --concurrency)
            concurrency="${2:-}"
            shift 2
            ;;
        --timeout)
            timeout_seconds="${2:-}"
            shift 2
            ;;
        --header)
            headers+=("${2:-}")
            shift 2
            ;;
        --request-id-prefix)
            request_id_prefix="${2:-}"
            shift 2
            ;;
        --no-request-id)
            send_request_id=0
            shift
            ;;
        --output)
            output_file="${2:-}"
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

if [[ -z "$url" ]]; then
    echo "--url is required" >&2
    usage
    exit 1
fi

if ! [[ "$requests" =~ ^[0-9]+$ ]] || [[ "$requests" -le 0 ]]; then
    echo "--requests must be a positive integer" >&2
    exit 1
fi

if ! [[ "$concurrency" =~ ^[0-9]+$ ]] || [[ "$concurrency" -le 0 ]]; then
    echo "--concurrency must be a positive integer" >&2
    exit 1
fi

if ! [[ "$timeout_seconds" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
    echo "--timeout must be a positive number" >&2
    exit 1
fi

tmp_dir="$(mktemp -d)"
raw_file="$tmp_dir/results.raw"
sorted_file="$tmp_dir/results.sorted"
worker_script="$tmp_dir/worker.sh"

cleanup() {
    rm -rf "$tmp_dir"
}
trap cleanup EXIT

cat > "$worker_script" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

idx="$1"
url="$2"
method="$3"
timeout_seconds="$4"
request_id_prefix="$5"
shift 5

args=(
  --silent
  --show-error
  --output /dev/null
  --max-time "$timeout_seconds"
  --request "$method"
)

for header in "$@"; do
    args+=(--header "$header")
done

if [[ -n "$request_id_prefix" ]]; then
    args+=(--header "X-Request-Id: ${request_id_prefix}${idx}")
fi

args+=(--write-out "%{http_code} %{time_total}\n" "$url")

if ! curl "${args[@]}" 2>/dev/null; then
    printf "000 %s\n" "$timeout_seconds"
fi
EOF

chmod +x "$worker_script"

effective_request_id_prefix="$request_id_prefix"
if [[ "$send_request_id" -eq 0 ]]; then
    effective_request_id_prefix=""
fi

start_epoch_ns="$(date +%s%N)"

seq "$requests" | xargs -P "$concurrency" -I{} "$worker_script" "{}" "$url" "$method" "$timeout_seconds" "$effective_request_id_prefix" "${headers[@]}" > "$raw_file"

end_epoch_ns="$(date +%s%N)"

elapsed_seconds="$(awk -v start="$start_epoch_ns" -v end="$end_epoch_ns" 'BEGIN {
  diff = (end - start) / 1000000000
  if (diff <= 0) diff = 0.001
  printf "%.6f", diff
}')"

if [[ -n "$output_file" ]]; then
    cp "$raw_file" "$output_file"
fi

sort -k2,2n "$raw_file" > "$sorted_file"

total="$(wc -l < "$raw_file" | tr -d ' ')"

if [[ "$total" -eq 0 ]]; then
    echo "No results captured."
    exit 1
fi

p50_idx=$(( (total + 1) / 2 ))
p95_idx=$(( (total * 95 + 99) / 100 ))
p99_idx=$(( (total * 99 + 99) / 100 ))

p50="$(awk -v row="$p50_idx" 'NR == row { print $2 }' "$sorted_file")"
p95="$(awk -v row="$p95_idx" 'NR == row { print $2 }' "$sorted_file")"
p99="$(awk -v row="$p99_idx" 'NR == row { print $2 }' "$sorted_file")"

stats="$(awk '
{
  code = $1 + 0
  latency = $2 + 0
  sum += latency
  if (NR == 1 || latency < min) min = latency
  if (NR == 1 || latency > max) max = latency
  if (code >= 200 && code < 300) ok2xx++
  else if (code >= 300 && code < 400) ok3xx++
  else if (code >= 400 && code < 500) err4xx++
  else if (code >= 500 && code < 600) err5xx++
  else errNet++
}
END {
  avg = (NR > 0) ? sum / NR : 0
  printf "%d %d %d %d %d %.6f %.6f %.6f\n", ok2xx + 0, ok3xx + 0, err4xx + 0, err5xx + 0, errNet + 0, avg, min + 0, max + 0
}
' "$raw_file")"

read -r count_2xx count_3xx count_4xx count_5xx count_net avg min max <<< "$stats"

rps="$(awk -v total="$total" -v sec="$elapsed_seconds" 'BEGIN {
  if (sec <= 0) sec = 0.001
  printf "%.2f", total / sec
}')"

echo "Load test summary"
echo "URL: $url"
echo "Method: $method"
echo "Requests: $total"
echo "Concurrency: $concurrency"
echo "Duration: ${elapsed_seconds}s"
echo "Throughput (req/s): $rps"
echo
echo "Status counts"
echo "2xx: $count_2xx"
echo "3xx: $count_3xx"
echo "4xx: $count_4xx"
echo "5xx: $count_5xx"
echo "Network/timeout(000): $count_net"
echo
echo "Latency (seconds)"
printf "min: %s\n" "$min"
printf "avg: %s\n" "$avg"
printf "p50: %s\n" "$p50"
printf "p95: %s\n" "$p95"
printf "p99: %s\n" "$p99"
printf "max: %s\n" "$max"
