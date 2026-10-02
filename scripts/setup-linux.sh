#!/usr/bin/env bash
#
# Prumo — setup automático em VPS Linux (Ubuntu/Debian).
# Uso: bash scripts/setup-linux.sh   (rode de qualquer lugar)
# O script sobe um diretório (raiz do projeto) e configura tudo:
# PHP 8.3 + Composer + Node 20 + Tesseract (com português) + app.
set -euo pipefail

# Sobe para a raiz do projeto (este script mora em scripts/).
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

info() { echo "==> $*"; }
ok() { echo "    [ok] $*"; }
have() { command -v "$1" >/dev/null 2>&1; }

if [ "$(id -u)" -ne 0 ] && have sudo; then
    SUDO="sudo"
else
    SUDO=""
fi

# ── 1. Pacotes do sistema ─────────────────────────────
info "Instalando pacotes do sistema (PHP 8.3, Tesseract, git)..."
$SUDO apt-get update -qq
$SUDO apt-get install -y -qq \
    php8.3-cli php8.3-mbstring php8.3-xml php8.3-sqlite3 \
    php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath php8.3-gd \
    tesseract-ocr tesseract-ocr-por \
    git unzip curl ca-certificates
ok "PHP $(php -r 'echo PHP_VERSION;') + tesseract $(tesseract --version 2>/dev/null | head -1)"

# ── 2. Composer ───────────────────────────────────────
if ! have composer; then
    info "Instalando Composer..."
    EXPECTED=$(curl -fsSL https://composer.github.io/installer.sig)
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    php -r "if (hash_file('SHA384', '/tmp/composer-setup.php') !== '$EXPECTED') { echo 'Assinatura inválida'; exit(1); }"
    $SUDO php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm -f /tmp/composer-setup.php
fi
ok "composer $(composer --version | awk '{print $3}')"

# ── 3. Node 20 (para o build do frontend) ─────────────
if ! have node; then
    info "Instalando Node 20..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | $SUDO bash - >/dev/null
    $SUDO apt-get install -y -qq nodejs
fi
ok "node $(node -v)"

# ── 4. Dependências PHP ───────────────────────────────
info "Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction
ok "vendor pronto"

# ── 5. Ambiente (.env) ────────────────────────────────
if [ ! -f .env ]; then
    cp .env.example .env
    ok ".env criado a partir do exemplo"
fi
php artisan key:generate --force >/dev/null
ok "APP_KEY gerada"

# ── 6. Banco (SQLite por padrão) ──────────────────────
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --force
ok "migrations aplicadas"

# ── 7. Frontend ───────────────────────────────────────
info "Instalando dependências JS e gerando o build..."
npm ci --no-audit --no-fund 2>/dev/null || npm install --no-audit --no-fund
npm run build >/dev/null
ok "build em public/build"

# ── 8. Permissões ─────────────────────────────────────
chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true
ok "permissões de escrita em storage/"

echo ""
echo "Pronto! Próximos passos:"
echo "  1. Ajuste o .env (APP_URL, banco, BILLING_* , BOT_*, credenciais)."
echo "  2. Desenvolvimento: php artisan serve --host=0.0.0.0 --port=8000"
echo "  3. Produção: aponte o nginx para $ROOT/public e ative o cron:"
echo "     * * * * * cd $ROOT && php artisan schedule:run >> /dev/null 2>&1"
