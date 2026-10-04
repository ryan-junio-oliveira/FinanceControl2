#!/bin/sh
# Entrypoint dos containers PHP (app / worker / scheduler).
# CONTAINER_MODE=app executa migrations + cache de config; demais modos só aguardam o banco.
set -e

if [ -z "$APP_KEY" ]; then
  echo "ERRO: APP_KEY vazia. Gere uma chave e defina no .env.docker (php artisan key:generate --show)." >&2
  exit 1
fi

# APP_URL precisa ser uma URL válida com host não vazio. Sem isso, o boot do
# Laravel quebra com "Invalid URI: Host is malformed." e o container entra em
# loop de restart com log críptico (ex.: APP_URL=http:// :50000 quando a
# detecção de IP do up-lan falha). Fail-fast com mensagem clara.
if [ "${APP_URL:-}" != "${APP_URL#http://}" ]; then
  _hostport=${APP_URL#http://}
elif [ "${APP_URL:-}" != "${APP_URL#https://}" ]; then
  _hostport=${APP_URL#https://}
else
  echo "ERRO: APP_URL='${APP_URL:-}' inválida (use http://host:porta ou https://host)." >&2
  exit 1
fi
_host=${_hostport%%[/:]*}
_host=$(echo "$_host" | tr -d '[:space:]')
if [ -z "$_host" ]; then
  echo "ERRO: APP_URL='${APP_URL:-}' sem host válido (ex.: http://192.168.0.10:50000)." >&2
  exit 1
fi
unset _hostport _host

echo "Aguardando banco de dados (${DB_HOST:-db}:${DB_PORT:-3306})..."
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
  sleep 2
done
echo "Banco acessível."

if [ "${CONTAINER_MODE:-app}" = "app" ]; then
  echo "Rodando migrations..."
  php artisan migrate --force
  echo "Otimizando caches..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
