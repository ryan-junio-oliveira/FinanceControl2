#!/usr/bin/env bash
# Sobe servidor + túnel cloudflared e registra o webhook do bot.
# Uso: bash scripts/start-bot.sh  (ou ./scripts/start-bot.sh)
set -e
cd "$(dirname "$0")/.."
LOGFILE="storage/logs/cloudflared.log"

if curl -s -o /dev/null --max-time 2 http://localhost:8000/login; then
  echo "[1/3] Servidor já rodando na porta 8000."
else
  echo "[1/3] Iniciando php artisan serve..."
  (nohup php artisan serve --host=0.0.0.0 --port=8000 > storage/logs/serve.log 2>&1 &)
  sleep 6
fi

CLOUDFLARED=""
for c in "C:/Program Files (x86)/cloudflared/cloudflared.exe" "C:/Program Files/cloudflared/cloudflared.exe"; do
  [ -f "$c" ] && CLOUDFLARED="$c" && break
done
[ -z "$CLOUDFLARED" ] && CLOUDFLARED="$(command -v cloudflared || true)"
if [ -z "$CLOUDFLARED" ]; then
  echo "ERRO: cloudflared não encontrado. Instale com: winget install Cloudflare.cloudflared"
  exit 1
fi

rm -f "$LOGFILE"
echo "[2/3] Iniciando cloudflared (aguarde a URL)..."
(nohup "$CLOUDFLARED" tunnel --url http://localhost:8000 --loglevel warn --logfile "$LOGFILE" > /dev/null 2>&1 &)

URL=""
for _ in $(seq 1 40); do
  URL="$(grep -oE 'https://[a-zA-Z0-9.-]+\.trycloudflare\.com' "$LOGFILE" 2>/dev/null | head -1)"
  [ -n "$URL" ] && break
  sleep 1
done
if [ -z "$URL" ]; then
  echo "Não consegui obter a URL do túnel. Veja $LOGFILE"
  exit 1
fi

echo "URL do túnel: $URL"
echo "[3/3] Registrando webhook..."
php artisan bot:telegram-webhook "$URL/api/bot/telegram"

echo
echo "Bot no ar. Teste no celular o @finfamiliaapp_bot."