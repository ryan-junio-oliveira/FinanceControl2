# Infra Docker (produção)

> **Modelo: instância única multi-tenant.** O sistema usa um só banco com
> schema compartilhado e isolamento por `group_id` (`scopeOfGroup`,
> Policies, `group.ownership`). Todos os tenants dividem o mesmo `app`,
> `db`, `redis` e `rabbitmq` — existe **um deploy**, não um por cliente.
> Escala vertical (mais CPU/RAM no host) + workers; escalar horizontalmente
> o `app` só com sessão/cache em Redis (já configurado) e storage
> compartilhado.

Stack: `nginx:1.28` → `app` (PHP 8.5-fpm) + `db` (MySQL 8.4) + `redis:8`
(cache/sessão/filas leves) + `rabbitmq:4` (filas pesadas) + `worker`
(RabbitMQ) + `worker-redis` + `scheduler` (cron Laravel). Volumes
persistentes: `db_data`, `redis_data`, `rabbit_data`, `storage_data`,
`backup_data`. Ver `documentation/backup.md` para o backup.

```
                 ┌─────────┐
                 │  nginx  │ :8080
                 └────┬────┘
                      │ fastcgi app:9000
                 ┌────▼────┐      ┌──────────┐  ┌───────────┐
                 │   app   ├──────┤  db :3306│  │phpmyadmin │
                 │ php-fpm │      │ mysql:8.4│  │   :8081   │
                 └────┬────┘      └──────────┘  └───────────┘
          ┌───────────┼───────────────────┐
          │           │                   │
     ┌────▼───┐  ┌────▼────┐         ┌─────▼──────┐
     │ redis  │  │ rabbitmq│         │ scheduler  │ schedule:work
     │  :6379 │  │5672/15672         │ 03:00 db:backup
     └────┬───┘  └────┬────┘         │ 00/15 market:warm
          │           │              │ 08:00 notify:vencimentos
   ┌──────▼──────┐ ┌──▼────────┐      └────────────┘
   │worker-redis │ │  worker   │
   │queue: redis │ │queue: rmq │
   └─────────────┘ └───────────┘
   /backups = backup_data (app:rw, scheduler:rw, worker:ro)
```

## Subida (primeira vez)

> Todos os comandos `docker compose` desta página exigem
> `--env-file .env.docker` (o Compose não lê esse arquivo sozinho).

```sh
cp .env.docker.example .env.docker
# edite: APP_KEY (php artisan key:generate --show), APP_URL, senhas DB_*/RABBITMQ_*, MAIL_*, MP_*
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker ps    # todos healthy/running
docker compose --env-file .env.docker exec prumo-app php artisan migrate --force
docker compose --env-file .env.docker exec prumo-app php artisan banks:seed
```

## Rede local (atalho Windows)

`scripts\up-lan.bat`: detecta o IP da máquina, ajusta `APP_URL`,
libera a porta 8080 no firewall e sobe tudo — abra `http://<IP>:8080`
no celular (mesmo Wi-Fi). `scripts\stop-stack.bat`: `down` **sem `-v`**
(volumes/banco preservados — `-v` apagaria tudo).

`entrypoint.sh` gera `APP_KEY`? Não — aborta sem `APP_KEY` (fail-fast).
Migrations + `config/route/view:cache` rodam no boot do `app`.

## Variáveis principais (`.env.docker`)

| Grupo | Vars |
|---|---|
| App | `APP_KEY/URL/ENV=production/DEBUG=false`, `APP_TIMEZONE` |
| MySQL | `DB_HOST=db`, `DB_DATABASE/USERNAME/PASSWORD/ROOT_PASSWORD`, `MYSQL_INNODB_BUFFER_POOL_SIZE`, `MYSQL_MAX_CONNECTIONS` |
| Cache/fila | `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=rabbitmq`, `REDIS_HOST=redis`, `RABBITMQ_HOST=rabbitmq` + credenciais |
| Backup | `BACKUP_PATH=/backups`, `BACKUP_KEEP_*`, `BACKUP_S3_*` (opcional) |
| HTTP | `NGINX_PORT=8080`, `PMA_PORT=8081` |

## Operação

```sh
docker compose --env-file .env.docker up -d --build   # deploy (rebuild app/workers)
docker compose --env-file .env.docker logs -f prumo-app prumo-scheduler prumo-worker prumo-worker-redis prumo-db
docker compose --env-file .env.docker exec prumo-app php artisan tinker
docker compose --env-file .env.docker exec prumo-app php artisan queue:failed
docker compose --env-file .env.docker exec prumo-scheduler php artisan schedule:list
docker compose --env-file .env.docker exec prumo-scheduler php artisan db:backup        # backup manual
docker compose --env-file .env.docker exec prumo-scheduler php artisan db:restore ARQ --force
```

## Containers (`prumo-<serviço>`)

| Container | Serviço | Porta host |
|---|---|---|
| `prumo-app` | PHP-FPM (migrations + cache no boot) | — (via nginx) |
| `prumo-nginx` | Nginx | `${NGINX_PORT:-8080}` → 80 |
| `prumo-db` | MySQL 8.4 | `${DB_PORT_PUBLISHED:-3306}` → 3306 |
| `prumo-phpmyadmin` | phpMyAdmin (login com senha, sempre) | `${PMA_PORT:-8081}` → 80 |
| `prumo-redis` | Redis (cache/sessão/filas leves) | 6379 |
| `prumo-rabbit` | RabbitMQ + management | 5672 / 15672 |
| `prumo-worker` | Fila RabbitMQ (OCR, bot, notificações) | — |
| `prumo-worker-redis` | Fila Redis (e-mail, auditoria, mercado) | — |
| `prumo-scheduler` | `schedule:work` (warm, vencimentos, backup) | — |

Manutenção direta: `docker compose logs -f prumo-worker`,
`docker compose exec prumo-db mysql -uroot -p`, etc. Se ainda existirem
containers antigos `finfamiglia-*`, recrie: `docker compose down &&
docker compose up -d --build` (volumes de dados são preservados).

## phpMyAdmin — senha sempre obrigatória

Auth `cookie`: toda sessão exige usuário+senha do MySQL (root ou
`DB_USERNAME`). Regras:

- `PMA_USER`/`PMA_PASSWORD` **nunca** definidos (isso pularia o login);
- `DB_ROOT_PASSWORD` e `DB_PASSWORD` fortes e diferentes entre si
  (`openssl rand -base64 32`);
- MySQL 8.4 não cria usuário anônimo; todo acesso passa por senha;
- sessão ociosa expira; em VPS, restrinja a porta 8081 no firewall ou
  publique só em localhost (`127.0.0.1:8081:80`).

## Código reflete na hora (bind mount local)

O `docker-compose.override.yml` (carregado automaticamente) monta `.` em
`/var/www` no `app/worker/scheduler` + `php-dev.ini` (opcache com
revalidação a cada 2s). Editar aqui = vale no container, sem rebuild.
`vendor/` e `public/build` ficam em volumes anônimos (não são
sobrescritos). Produção: suba **sem** o override:

```sh
docker compose --env-file .env.docker -f docker-compose.yml up -d --build
```

Deploy sem downtime relevante: `up -d --build` recria um serviço por vez;
`opcache.validate_timestamps=0` exige rebuild/recreate do `app` a cada
deploy (o `config:cache` é refeito no entrypoint).

## Dados e volumes

| Volume | Conteúdo | Some se apagar? |
|---|---|---|
| `db_data` | `/var/lib/mysql` | **SIM — todo o banco** (restore via `/backups`) |
| `backup_data` | `/backups` (`*.sql.gz` + manifestos) | cópias de segurança |
| `storage_data` | `storage/` (anexos, logs) | anexos/OCR |
| `redis_data` | AOF (cache/sessão/fila) | reconstruível (sessões caem) |
| `rabbit_data` | filas | jobs não processados |

Nunca rode `docker compose down -v` em produção. Backup do host:
`docker run --rm -v prumo_db_data:/d -v /srv:/b alpine tar czf
/b/db-$(date +%F).tgz /d` (frio; preferir `db:backup` a quente).

## MySQL 8.4 — notas

- `command` ajusta charset `utf8mb4/unicode_ci`, `innodb-buffer-pool`
  (suba p/ ~70% RAM em VPS dedicada), `max-connections` e expiração de
  binlog (7 dias).
- Healthcheck `mysqladmin ping` com `start_period` 30s (bootstrap lento).
- Migração MariaDB → MySQL: `db:backup` no antigo → `up -d db` novo
  (volume limpo) → `db:restore --force` → `migrate --force`.

## Segurança mínima

- `.env.docker` nunca commitado; `APP_DEBUG=false`;
  `SESSION_SECURE_COOKIE=true` (HTTPS); secrets fortes
  (`openssl rand -base64 32`).
- phpMyAdmin com login e senha obrigatórios (cookie auth, sem bypass);
  nunca exponha a porta sem firewall em VPS.
- RabbitMQ management (15672) e MySQL (3306) publicados por conveniência —
  em VPS, restrinja via firewall/SG ou remova `ports:` e use rede interna.
- `SESSION_ENCRYPT=true`; `opcache` + `expose_php=Off`.

## Troubleshooting

| Sintoma | Ação |
|---|---|
| `app` exit 1 `ERRO: APP_KEY vazia` | defina `APP_KEY` no `.env.docker` |
| `Banco acessível` nunca aparece | `logs db`, credenciais `DB_*`, healthcheck |
| 502 no nginx | `app:9000` fora do ar? `logs app`, `ps`, rebuild |
| Filas paradas | `logs worker*`, `queue:failed`, DLQ RabbitMQ `:15672` |
| Scheduler não dispara | `logs scheduler`, `schedule:list`, timezone |
| Backup ausente às 03:00 | ver `documentation/backup.md` |
| Build falha no `pecl` (download truncado) | retry automático no Dockerfile; rode o build de novo |
| Build falha em extensão PHP | base é `php:8.5-fpm-alpine`; `opcache` já vem embutido (não instalar), `redis` pinado em 6.3.0 |
