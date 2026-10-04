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
| `vendor/bin/pint --test` | Checa estilo PSR-12 (sem alterar) |
| `vendor/bin/pint` | Formata código (PSR-12) |
| `php artisan l5-swagger:generate` | Gera documentação Swagger |
| `php artisan db:seed --class=PopulateDatabaseSeeder` | Popula dados fake 2026 |
| `php artisan banks:seed` | Semeia catálogo de bancos |
| `php artisan db:backup` | Backup gzip do MySQL (ver `backup.md`) |
| `php artisan db:restore {arq} --force` | Restaura backup (ver `backup.md`) |
| `php artisan queue:work rabbitmq` | Consome filas pesadas (OCR, bot) |
| `php artisan schedule:list` | Mostra agendamentos (warm, vencimentos, backup) |
| `php artisan optimize:clear` | Limpa caches |

> Produção roda no Docker (`documentation/infra-docker.md`): não use
> `php artisan serve` lá — suba com `scripts\up-lan.bat` (rede local) ou
> `docker compose --env-file .env.docker up -d --build`.

## Testes

```sh
php artisan test                    # suíte completa
php artisan test --filter=ApiTest   # apenas API
php artisan test --filter=PrumoBackendTest  # apenas web
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
│   ├── Middleware/          # group.role, group.ownership
│   ├── Requests/            # FormRequests
│   └── Resources/           # API Resources
├── Models/                  # Eloquent
├── Observers/               # Auditoria (+ invalidação de cache)
├── Services/                # Domínio (reutilizável)
├── Jobs/                    # Filas (bot, OCR, vencimentos)
├── Policies/                # Autorização por recurso
├── Mail/ + Notifications/   # E-mail (fila) e avisos in-app
├── Console/Commands/        # Artisan (backup, seeds, warm, notify)
└── Support/                 # Helpers (Fin, Audit, Dashboard)
```
