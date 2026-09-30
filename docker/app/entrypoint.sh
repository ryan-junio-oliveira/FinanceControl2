#!/bin/sh
# Entrypoint dos containers PHP (app / worker / scheduler).
# CONTAINER_MODE=app executa migrations + cache de config; demais modos só aguardam o banco.
set -e

if [ -z "$APP_KEY" ]; then
  echo "ERRO: APP_KEY vazia. Gere uma chave e defina no .env.docker (php artisan key:generate --show)." >&2
  exit 1
fi

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
