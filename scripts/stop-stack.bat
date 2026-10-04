@echo off
REM Prumo - derruba os containers da stack COM SEGURANCA.
REM `down` SEM `-v`: remove containers e rede, mas PRESERVA os volumes
REM (banco MySQL, backups, storage, redis, rabbit).
REM
REM  NUNCA rode `down -v` aqui: o `-v` APAGA os volumes e DESTROI o banco
REM  de dados e os backups locais. Em producao isso e perda total de dados.
REM Uso: scripts\stop-stack.bat
setlocal
cd /d "%~dp0.."

if exist .env.docker (
    docker compose --env-file .env.docker down
) else (
    docker compose down
)
if errorlevel 1 exit /b 1

echo Pilha parada com seguranca (volumes preservados).
echo Para subir de novo: scripts\up-lan.bat
endlocal
