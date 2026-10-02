# Laravel Application

Este repositório contém uma aplicação Laravel (Prumo — gestão financeira familiar).

## Pré-requisitos

Verifique que PHP e Composer estão disponíveis:

```sh
php -v
composer -V
```

## Setup

Setup automático (recomendado — o script sobe para a raiz do projeto sozinho):

```sh
bash scripts/setup-linux.sh      # VPS Ubuntu/Debian (instala PHP 8.3, Tesseract + por, Composer, Node)
scripts\setup-windows.bat        # Windows (duplo clique; Tesseract portátil já vem em bin/)
```

Manual:

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Servir localmente:

```sh
php artisan serve
```

## Convenções do projeto

- **Frontend**: Blade + Tailwind CSS (utilitários) via Vite. Evite CSS customizado quando o Tailwind já cobre.
- **Máscaras monetárias**: inputs usam `inputmode="decimal"`; o IMask aplica o formato `1.234,56`; o backend normaliza via `normalizeMoney()` nas Form Requests.
- **Validação**: usar Form Requests em `app/Http/Requests` com mensagens amigáveis pt-BR (`lang/pt_BR/`).
- **Efeitos visuais**: libs em `resources/js/app.js` (gsap, scrollreveal, nprogress, imask, three no login). Respeitam `prefers-reduced-motion`.
- **Testes**: `php artisan test`. Rodar sempre após mudanças; manter `storage/logs/laravel.log` vazio.

## Dados de desenvolvimento

- **Locale**: `APP_LOCALE=pt_BR` via `lucascudo/laravel-pt-br-localization` (arquivos em `lang/pt_BR/` + `lang/pt_BR.json`).
- **Bancos do catálogo**: `php artisan banks:seed` (idempotente, ~112 bancos com cor da marca).
- **População fake 2026**: `php artisan db:seed --class=PopulateDatabaseSeeder` — usa o usuário #1/família #1 e recria contas, cartões, lançamentos, cartão de crédito, investimentos e membros do ano de 2026 (reexecutável).
- **Logs de auditoria**: trilha de todas as ações (criação/edição/exclusão de lançamentos, contas, cartões, categorias, investimentos, membros, login/logout, configurações) com quem, IP, método, URL e mudanças. Acessível só pelo admin em `/admin/logs` (`App\Support\Audit` + observers; ignora CLI/seed; `Audit::silence` para limpezas em massa).
- **Bot**: módulo `App\Bot` com driver trocável (`BOT_DRIVER`: `telegram` grátis/ilimitado, `whatsapp` futuro); menu espelha a sidebar; vínculo via `bot_code` do perfil; webhook em `/api/bot/telegram` (`php artisan bot:telegram-webhook`).