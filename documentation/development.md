# Desenvolvimento

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan banks:seed
npm install
npm run build
php artisan serve
```

## Comandos úteis

| Comando | Descrição |
|---------|-----------|
| `php artisan test` | Roda a suíte de testes |
| `vendor/bin/pint` | Formata código (PSR-12) |
| `php artisan l5-swagger:generate` | Gera documentação Swagger |
| `php artisan db:seed --class=PopulateDatabaseSeeder` | Popula dados fake 2026 |
| `php artisan banks:seed` | Semeia catálogo de bancos |
| `php artisan optimize:clear` | Limpa caches |

## Testes

```sh
php artisan test                    # suíte completa
php artisan test --filter=ApiTest   # apenas API
php artisan test --filter=FinfamBackendTest  # apenas web
```

## Convenções

- **Controllers**: finos, apenas HTTP
- **Services**: regras de domínio (reutilizáveis)
- **FormRequests**: validação + mensagens pt-BR
- **Resources**: transformação JSON (API)
- **Observers**: auditoria automática
- **Estilo**: PSR-12 via Pint

## Estrutura

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/          # API REST
│   │   └── Auth/            # Autenticação web
│   ├── Middleware/          # family.role, family.ownership
│   ├── Requests/            # FormRequests
│   └── Resources/           # API Resources
├── Models/                  # Eloquent
├── Observers/               # Auditoria
├── Services/                # Domínio (reutilizável)
└── Support/                 # Helpers (Fin, Audit, Dashboard)
```
