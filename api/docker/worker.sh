#!/bin/sh
# Worker Symfony — Messenger + Scheduler
set -e

echo "[worker] Attente base de données (5s)..."
sleep 5

echo "[worker] Démarrage : async + scheduler_default"
exec php bin/console messenger:consume async scheduler_default \
  --time-limit=3600 \
  --memory-limit=256M \
  --failure-limit=3 \
  -vv
