#!/bin/sh
# ─────────────────────────────────────────────────────────────────────────────
# Scheduler entrypoint
# Runs `php artisan schedule:run` every 60 seconds.
# Laravel Scheduler requires minute-level granularity, so we loop manually
# instead of relying on cron (which is unavailable in minimal Alpine images).
# ─────────────────────────────────────────────────────────────────────────────

echo "[scheduler] Starting Mockwave scheduler loop..."

while true; do
    echo "[scheduler] $(date '+%Y-%m-%d %H:%M:%S') — running schedule:run"
    php /var/www/artisan schedule:run --no-interaction 2>&1
    sleep 60
done
