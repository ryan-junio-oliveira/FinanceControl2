# FinFamília

Sistema de gestão financeira familiar — dashboard, despesas, receitas, contas, cartões, investimentos e membros da família.

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [API](documentation/api.md) | Referência completa da REST API v1 (Swagger/OpenAPI) |
| [Bot](documentation/bot.md) | Assistente conversacional no celular (Telegram/WhatsApp) |
| [Bot: setup Telegram](documentation/bot-telegram-setup.md) | Guia de configuração do bot |
| [PWA](documentation/pwa.md) | App instalável no celular |
| [Arquitetura](documentation/architecture.md) | Padrões, camadas, SOLID e decisões técnicas |
| [Domínio](documentation/domain.md) | Regras de negócio, entidades e fluxos |
| [Desenvolvimento](documentation/development.md) | Setup, comandos, testes e convenções |

## Swagger UI

A documentação interativa da API está disponível em:

```
GET /api/documentation
```

## Stack

- **Backend**: Laravel 13 + PHP 8.3
- **Frontend**: Blade + Tailwind CSS (Vite)
- **API**: REST v1 com Sanctum (Bearer token)
- **Banco**: SQLite (dev) / MySQL (prod)
- **Testes**: PHPUnit
- **Estilo**: PSR-12 via Laravel Pint

## Quick start

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan banks:seed
npm install && npm run build
php artisan serve
```

## Testes

```sh
php artisan test
```
