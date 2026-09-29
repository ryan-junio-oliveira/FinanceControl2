# Laravel Application

Este repositório contém uma aplicação Laravel (FinFamília — gestão financeira familiar).

## Pré-requisitos

Verifique que PHP e Composer estão disponíveis:

```sh
php -v
composer -V
```

## Setup

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