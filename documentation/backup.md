# Backup do banco de dados (MySQL)

> **Instância única multi-tenant:** há um só banco com todos os grupos
> (`group_id`). Um `db:backup` cobre **todos os tenants de uma vez** —
> não existe backup por cliente no nível do banco. O restore também é
> tudo-ou-nada: para recuperar **um grupo específico**, use o export
> LGPD dela (`perfil.export`, streaming por cursor) como complemento, não
> o restore global.

Backup lógico diário, sem dependência externa: `mysqldump
--single-transaction` (não trava escritas InnoDB) + `gzip` + manifesto
JSON com SHA256. Roda no scheduler (03:00) e grava no volume `backup_data`
(`/backups` no Docker, `storage/app/backups` no dev).

## Comandos

> No Docker, prefixe com `docker compose --env-file .env.docker exec prumo-scheduler`
> (ex.: `docker compose --env-file .env.docker exec prumo-scheduler php artisan db:backup`).

| Comando | Uso |
|---|---|
| `php artisan db:backup` | Backup manual agora (verifica gzip + gera manifesto + aplica retenção) |
| `php artisan db:backup --no-verify` | Pula a verificação (não recomendado) |
| `php artisan db:restore {arquivo} --force` | Restaura `.sql.gz` (faz backup de segurança antes, confere SHA) |
| `php artisan schedule:run` | Dispara o agendador (o `schedule:work` do container faz isso a cada minuto) |

Em SQLite/dev os comandos só avisam e saem com sucesso (backup real exige
MySQL/MariaDB + binários `mysqldump`/`mysql` no PATH).

## Agendamento

`routes/console.php`:

```php
Schedule::command('db:backup')->dailyAt('03:00')->withoutOverlapping(60);
```

## Retenção (GFS simples)

- `BACKUP_KEEP_DAILY=7` — últimos 7 dias sempre mantidos;
- `BACKUP_KEEP_WEEKLY=4` — + 4 domingos;
- `BACKUP_KEEP_MONTHLY=6` — + 6 dias 1º de mês.

Arquivos fora da política (com seu `.manifest.json`) são apagados
automaticamente a cada backup. Ajuste via `.env` ou flags
`--keep-daily/--keep-weekly/--keep-monthly`.

## Arquivos

`/backups/prumo-db-2026-10-03-030000.sql.gz` + `.manifest.json`:

```json
{
  "file": "prumo-db-2026-10-03-030000.sql.gz",
  "size_bytes": 12345678,
  "sha256": "…",
  "duration_s": 4.2,
  "verified": true
}
```

## Restore (procedimento)

1. Liste: `ls /backups/*.sql.gz` (ou no host: `docker volume inspect
   prumo_backup_data`);
2. Segurança automática: `db:restore` roda `db:backup` antes (pule só com
   `--no-safety-backup` se o banco estiver vazio);
3. `docker compose exec scheduler php artisan db:restore prumo-db-2026-10-03-030000.sql.gz --force`;
4. `docker compose exec app php artisan migrate --force` (se o dump for
   anterior ao schema atual);
5. Confira: login + `admin/logs` + `dashboard`.

Produção exige `--force`. Fora de produção o comando pede confirmação
interativa.

## Offsite (3-2-1)

Local (volume) + cópia externa: defina `BACKUP_S3_DISK=s3` (+ credenciais
`AWS_*`) e todo backup é enviado para `BACKUP_S3_PREFIX` sem quebrar o
fluxo local se o S3 falhar (aviso no log). Recomendado: S3 com
versionamento + lifecycle (30 dias → Glacier). Sem S3, copie `/backups`
para fora via cron do host:

```sh
# /etc/cron.d/prumo-backup — espelho diário 04:00 para o host/NAS
0 4 * * * root docker cp prumo-scheduler:/backups /srv/prumo-offsite/$(date +\%F) && find /srv/prumo-offsite -maxdepth 1 -mtime +14 -exec rm -rf {} +
```

## Recuperação de desastre (checklist)

1. `docker compose up -d db` (volume `db_data` intacto? se perdido, segue);
2. Restore do último manifesto `verified: true`;
3. `migrate --force`, `config:cache`, `route:cache`;
4. Teste de fumaça: `/login`, `/dashboard`, `/api/v1/dashboard`;
5. Se o volume `db_data` foi perdido: `up -d` recria vazio → restore
   preenche → `banks:seed`/`categories:seed` só se o dump for muito antigo.

## Teste de restore (faça 1x/mês)

```sh
docker compose exec db mysql -uroot -p"$DB_ROOT_PASSWORD" -e "CREATE DATABASE prumo_restore_test"
zcat /backups/prumo-db-ULTIMO.sql.gz | docker compose exec -T db mysql -uroot -p"$DB_ROOT_PASSWORD" prumo_restore_test
docker compose exec db mysql -uroot -p"$DB_ROOT_PASSWORD" -e "DROP DATABASE prumo_restore_test"
```

## Troubleshooting

| Sintoma | Causa provável |
|---|---|
| `Nenhum binário mysqldump encontrado` | container sem `mariadb-client` (imagem oficial já inclui; rebuild) |
| Dump 0 bytes | credenciais `DB_*` erradas ou `db` fora do ar (`docker compose ps`, `logs db`) |
| `SHA256 divergente` | arquivo corrompido/incompleto — use o anterior + investigue disco |
| Scheduler não roda backup | `scheduler` parado ou `APP_ENV` sem `schedule:work` (`docker compose logs scheduler`) |
| Disco cheio | retenção muito alta ou S3 falhando silenciosamente — `df -h`, `du -sh /backups` |
