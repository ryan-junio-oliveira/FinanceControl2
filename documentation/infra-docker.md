# Infra Docker (produção)

> **Modelo: instância única multi-tenant.** O sistema usa um só banco com
> schema compartilhado e isolamento por `group_id` (`scopeOfGroup`,
> Policies, `group.ownership`). Todos os tenants dividem o mesmo `app`,
> `db`, `redis` e `rabbitmq` — existe **um deploy**, não um por cliente.
> Escala vertical (mais CPU/RAM no host) + workers; escalar horizontalmente
> o `app` só com sessão/cache em Redis (já configurado) e storage
> compartilhado.

Stack: `nginx:1.28` → `app` (PHP 8.4-fpm) + `db` (MySQL 8.4) + `redis:8`
(cache/sessão/filas leves) + `rabbitmq:4` (filas pesadas) + `worker`
(RabbitMQ) + `worker-redis` + `scheduler` (cron Laravel). Volumes
persistentes: `db_data`, `redis_data`, `rabbit_data`, `storage_data`,
`backup_data`. Ver `documentation/backup.md` para o backup.

```
                 ┌─────────┐
                  │  nginx  │ :50000
                  └────┬────┘
                       │ fastcgi app:9000
                  ┌────▼────┐      ┌──────────┐  ┌───────────┐
                  │   app   ├──────┤  db :50002│  │phpmyadmin │
                  │ php-fpm │      │ mysql:8.4│  │   :50001  │
                  └────┬────┘      └──────────┘  └───────────┘
           ┌───────────┼───────────────────┐
           │           │                   │
      ┌────▼───┐  ┌────▼────┐         ┌─────▼──────┐
      │ redis  │  │ rabbitmq│         │ scheduler  │ schedule:work
      │ :50003 │  │50004/50005        │ 03:00 db:backup
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
libera a porta 50000 no firewall e sobe tudo — abra `http://<IP>:50000`
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
| HTTP | `NGINX_PORT=50000`, `PMA_PORT=50001` |

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
| `prumo-nginx` | Nginx | `${NGINX_PORT:-50000}` → 80 |
| `prumo-db` | MySQL 8.4 | `${DB_PORT_PUBLISHED:-50002}` → 3306 |
| `prumo-phpmyadmin` | phpMyAdmin (login com senha, sempre) | `${PMA_PORT:-50001}` → 80 |
| `prumo-redis` | Redis (cache/sessão/filas leves) | 50003 |
| `prumo-rabbit` | RabbitMQ + management | 50004 / 50005 |
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
- sessão ociosa expira; em VPS, restrinja a porta 50001 no firewall ou
  publique só em localhost (`127.0.0.1:50001:80`).

## Código reflete na hora (bind mount local)

O `docker-compose.override.yml` (carregado automaticamente) monta `.` em
`/var/www` no `app/worker/scheduler` + `php-dev.ini` (opcache com
revalidação a cada 2s). Editar aqui = vale no container, sem rebuild.
`vendor/` fica em volume anônimo (não é sobrescrito); `public/build` é o
**build do host** (bind) — após mudar CSS/JS, rode `npm run build` e recrie
o `app`. Produção: suba **sem** o override:

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
- RabbitMQ management (50005) e MySQL (50002) publicados por conveniência —
  em VPS, restrinja via firewall/SG ou remova `ports:` e use rede interna.
- `SESSION_ENCRYPT=true`; `opcache` + `expose_php=Off`.

## Troubleshooting

| Sintoma | Ação |
|---|---|
| `app` exit 1 `ERRO: APP_KEY vazia` | defina `APP_KEY` no `.env.docker` |
| `Banco acessível` nunca aparece | `logs db`, credenciais `DB_*`, healthcheck |
| 502 no nginx | `app:9000` fora do ar? `logs app`, `ps`, rebuild |
| Filas paradas | `logs worker*`, `queue:failed`, DLQ RabbitMQ `:50005` |
| Scheduler não dispara | `logs scheduler`, `schedule:list`, timezone |
| Backup ausente às 03:00 | ver `documentation/backup.md` |
| Build falha no `pecl` (download truncado) | retry automático no Dockerfile; rode o build de novo |
| Build falha em extensão PHP | base é `php:8.4-fpm-alpine`; `opcache` via `docker-php-ext-install`, `redis` pinado em 6.3.0 |
| `Invalid URI: Host is malformed.` em loop | `APP_URL` sem host válido no `.env.docker` (ex.: `http:// :50000` após falha na detecção de IP); o entrypoint agora aborta com mensagem clara — corrija a URL e recrie os containers PHP |
| `ERR AUTH ...` no worker (Redis) | senha divergente: o compose repassa `REDIS_PASSWORD` ao servidor; use a mesma senha (ou vazia) nos dois lados |
| `Class ... not found` no boot (ex. Pail) | o override isola `/var/www/bootstrap/cache` por container; o cache do host (com deps dev) não sombreia mais o da imagem |
| 502 logo após recriar o `app` | nginx re-resolve o `app` via DNS `127.0.0.11`; se persistir, `up -d --force-recreate nginx` |
| 404 em `/build/assets/*` (sem CSS/JS) | manifest do app divergiu dos arquivos servidos: rode `npm run build` no host e recrie o `app` (`up -d --force-recreate app`) |
