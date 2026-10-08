#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

# PHP version can be passed as a variable now eg: ./bin/deploy.sh 8.4
PHP_VERSION="${1:-8.3}"
PHP="/opt/plesk/php/${PHP_VERSION}/bin/php"

if [[ ! -x "$PHP" ]]; then
  echo "PHP $PHP_VERSION not found or not executable: $PHP" >&2
  exit 1
fi

COMPOSER="$PHP $HOME/bin/composer.phar"
CONCRETE="$PHP vendor/bin/concrete"
LOG="$HOME/logs/deploy.log"

{
  echo "=== Deploy $(date -Is) $(git -C ~/git/*.git rev-parse --short HEAD 2>/dev/null || true)"
  $COMPOSER install --no-dev --prefer-dist --optimize-autoloader \
    --no-interaction --no-progress
  $CONCRETE c5:update -n            # core DB migrations
  $CONCRETE c5:package:update --all -n   # verify exact name, see below
  $CONCRETE c5:entities:refresh -n
  $CONCRETE c5:clear-cache -n
  echo "=== Done"
} >> "$LOG" 2>&1
