#!/bin/bash
# Arranca el servidor de Laravel (8000) y Vite (5173) si no están corriendo
# — idempotente a propósito: se dispara en cada inicio del Codespace
# (postStartCommand) y también desde ~/.bashrc, así que nunca debe duplicar
# procesos si ya están arriba.

cd "$(dirname "$0")/.."

LOG_DIR="storage/logs"
mkdir -p "$LOG_DIR"

if ! ss -tln 2>/dev/null | grep -q ':8000 '; then
    setsid nohup php artisan serve --host=0.0.0.0 --port=8000 > "$LOG_DIR/artisan-serve.log" 2>&1 < /dev/null &
    disown
fi

if ! ss -tln 2>/dev/null | grep -q ':5173 '; then
    setsid nohup npm run dev > "$LOG_DIR/vite-dev.log" 2>&1 < /dev/null &
    disown
fi
