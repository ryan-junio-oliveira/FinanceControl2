# Documentação do FinFamília

Bem-vindo à documentação técnica do FinFamília. Aqui você encontra tudo para desenvolver, testar e operar o sistema.

## Índice

| Documento | Descrição |
|-----------|-----------|
| [API](api.md) | Referência completa da REST API v1 (Swagger/OpenAPI) |
| [Bot](bot.md) | Assistente conversacional (Telegram/WhatsApp) |
| [Bot: setup Telegram](bot-telegram-setup.md) | Ligar o bot do zero ao primeiro `/start` |
| [Arquitetura](architecture.md) | Padrões, camadas, SOLID e decisões técnicas |
| [Domínio](domain.md) | Regras de negócio, entidades e fluxos |
| [Desenvolvimento](development.md) | Setup, comandos, testes e convenções |

## Visão rápida

- **Backend**: Laravel 13 + PHP 8.3, REST API v1 com Sanctum
- **Frontend**: Blade + Tailwind CSS via Vite
- **Banco**: SQLite (dev) / MySQL (prod)
- **Testes**: PHPUnit (`php artisan test`)
- **Estilo**: PSR-12 via Laravel Pint (`vendor/bin/pint`)
- **Documentação da API**: Swagger UI em `/api/documentation`
