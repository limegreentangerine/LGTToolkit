#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."   # project root (where composer.json and vendor/ live)

# Non-interactive runs (git hook/cron) have a minimal PATH, so add phpenv and Plesk PHP
export PHPENV_ROOT="${PHPENV_ROOT:-$HOME/.phpenv}"
export PATH="$PHPENV_ROOT/shims:$PHPENV_ROOT/bin:$(ls -d /opt/plesk/php/*/bin 2>/dev/null | sort -V | tail -n1):/usr/local/bin:$PATH"

COMPOSER="$HOME/.phpenv/shims/composer"
CONCRETE="./vendor/bin/concrete"
LOG="$HOME/logs/deploy.log"
NPM="$HOME/.nodenv/shims/npm"
mkdir -p "$(dirname "$LOG")"

# set -e aborts on any failing step; record it in the log and show the tail on the console
trap 'rc=$?; if [ "$rc" -ne 0 ]; then
  echo "=== FAILED (exit $rc) $(date -Is)" >> "$LOG"
  echo "Deploy FAILED (exit $rc). Last log lines ($LOG):" >&2
  tail -n 20 "$LOG" >&2
fi' EXIT

{
  echo "=== Deploy $(date -Is) $(git -C ~/git/*.git rev-parse --short HEAD 2>/dev/null || true)"
  $COMPOSER install --no-dev --prefer-dist --optimize-autoloader \
    --no-interaction --no-progress
  $CONCRETE c5:update -n            # core DB migrations
  $CONCRETE c5:package:update --all -n   # verify exact name, see below
  $CONCRETE c5:entities:refresh -n
  $CONCRETE c5:clear-cache -n
  $NPM ci
  $NPM run build
  echo "=== Done"
} >> "$LOG" 2>&1

echo "Deploy complete. Log: $LOG"
