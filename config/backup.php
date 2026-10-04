<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup do banco de dados (MySQL)
    |--------------------------------------------------------------------------
    | Dump lógico diário via mysqldump --single-transaction (sem travar
    | escritas InnoDB), comprimido com gzip, com manifesto JSON (sha256,
    | tamanho, duração) e retenção GFS simples: diários + semanais
    | (domingo) + mensais (dia 1). Offsite via S3 é opcional.
    */

    // Diretório dos dumps. Em Docker, monte o volume `backup_data` aqui.
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    // Prefixo dos arquivos: {prefix}-YYYY-MM-DD-HHMMSS.sql.gz
    'prefix' => env('BACKUP_PREFIX', 'prumo-db'),

    'keep_daily' => (int) env('BACKUP_KEEP_DAILY', 7),

    'keep_weekly' => (int) env('BACKUP_KEEP_WEEKLY', 4),

    'keep_monthly' => (int) env('BACKUP_KEEP_MONTHLY', 6),

    // Binário de dump/restore. `auto` detecta mysqldump → mariadb-dump → mysql.
    'dump_binary' => env('BACKUP_DUMP_BINARY', 'auto'),

    'mysql_binary' => env('BACKUP_MYSQL_BINARY', 'auto'),

    // Flags extras do dump (InnoDB-safe por padrão).
    'dump_options' => env(
        'BACKUP_DUMP_OPTIONS',
        '--single-transaction --quick --skip-lock-tables --routines --triggers --events --hex-blob --set-gtid-purged=OFF'
    ),

    // Offsite opcional (S3/MinIO). Se o bucket estiver vazio, só guarda local.
    's3_disk' => env('BACKUP_S3_DISK', null),

    's3_prefix' => env('BACKUP_S3_PREFIX', 'db-backups/'),

];
