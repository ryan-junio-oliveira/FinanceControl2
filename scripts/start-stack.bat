@echo off
setlocal
REM Prumo - sobe a pilha completa: servidor + tunel + webhook do bot.
REM Idempotente: pode rodar a qualquer hora; so inicia o que estiver parado.
REM Executado automaticamente no logon pela tarefa "PrumoStack".

REM Sobe um diretorio: a raiz do projeto.
cd /d "%~dp0.."
set "ROOT=%CD%"
set "LOG=%ROOT%\storage\logs\stack.log"

echo [%date% %time%] start-stack >> "%LOG%"

REM 1 - Servidor Laravel na porta 8000.
netstat -ano | findstr ":8000" | findstr "LISTENING" >nul
if %errorlevel% neq 0 (
    echo [1/3] Iniciando php artisan serve...
    start "Prumo-Server" /min cmd /k "cd /d %ROOT% && php artisan serve --host=0.0.0.0 --port=8000"
    powershell -NoProfile -Command "Start-Sleep -Seconds 6" >nul
) else (
    echo [1/3] Servidor ja ativo na porta 8000.
)
echo Servidor ok >> "%LOG%"

REM 1b - Frontend: sem Vite dev na 5173, remove public/hot obsoleto e usa o build.
netstat -ano | findstr ":5173" | findstr "LISTENING" >nul
if %errorlevel% neq 0 (
    if exist "%ROOT%\public\hot" del "%ROOT%\public\hot"
)

REM 2 - Tunel cloudflared: servico, processo ou tunel rapido novo.
sc query cloudflared 2>nul | findstr "RUNNING" >nul
if %errorlevel% equ 0 (
    echo [2/3] Tunel via servico ativo.
    goto webhook
)
tasklist /FI "IMAGENAME eq cloudflared.exe" 2>nul | findstr "cloudflared.exe" >nul
if %errorlevel% equ 0 (
    echo [2/3] Tunel via processo ativo.
    goto webhook
)

set "CLOUD="
if exist "%ProgramFiles(x86)%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles(x86)%\cloudflared\cloudflared.exe"
if "%CLOUD%"=="" if exist "%ProgramFiles%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles%\cloudflared\cloudflared.exe"
if "%CLOUD%"=="" for /f "delims=" %%i in ('where cloudflared 2^>nul') do if not defined CLOUD set "CLOUD=%%i"
if "%CLOUD%"=="" (
    echo [ERRO] cloudflared nao encontrado. Rode scripts\tunnel-install-windows.bat
    echo ERRO sem cloudflared >> "%LOG%"
    exit /b 1
)
echo [2/3] Subindo tunel rapido temporario...
if exist "%ROOT%\storage\logs\cloudflared.log" del "%ROOT%\storage\logs\cloudflared.log"
start "Prumo-Tunel" /min cmd /k ""%CLOUD%" tunnel --url http://localhost:8000 --loglevel warn --logfile "%ROOT%\storage\logs\cloudflared.log""

:webhook
REM 3 - URL publica: fixa via TUNNEL_URL no .env ou extrai do tunel rapido.
set "URL="
for /f "usebackq tokens=1* delims==" %%a in (`findstr /B "TUNNEL_URL=" "%ROOT%\.env" 2^>nul`) do (
    if "%%a"=="TUNNEL_URL" set "URL=%%b"
)
if defined URL set "URL=%URL:"=%"
if defined URL goto registrar
echo Aguardando URL publica do tunel...
for /l %%i in (1,1,40) do (
    if not defined URL call :extrai_url
    if not defined URL powershell -NoProfile -Command "Start-Sleep -Seconds 1" >nul
)

:registrar
if "%URL%"=="" (
    echo [ERRO] Nao foi possivel obter a URL publica.
    echo ERRO sem URL >> "%LOG%"
    exit /b 1
)
echo URL: %URL%
echo [3/3] Registrando webhook do bot...
cd /d "%ROOT%" && php artisan bot:telegram-webhook "%URL%/api/bot/telegram"
echo webhook %URL% >> "%LOG%"
echo.
echo Pilha no ar. Bot: @finfamiliaapp_bot
exit /b 0

:extrai_url
powershell -NoProfile -Command "(Select-String -Path '%ROOT%\storage\logs\cloudflared.log' -Pattern 'https://[a-z0-9.-]+\.trycloudflare\.com' -AllMatches).Matches.Value | Select-Object -First 1" > "%TEMP%\prumo-url.txt" 2>nul
for /f "usebackq delims=" %%u in (`type "%TEMP%\prumo-url.txt" 2^>nul`) do set "URL=%%u"
del "%TEMP%\prumo-url.txt" 2>nul
goto :eof
