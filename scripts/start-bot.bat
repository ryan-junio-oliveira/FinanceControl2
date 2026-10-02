@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
cd /d "%~dp0.."

echo ================================================
echo   FinFamilia — Bot (servidor + tunel + webhook)
echo ================================================

REM 1) Servidor
netstat -ano | findstr :8000 >nul
if %errorlevel% equ 0 (
    echo [1/3] Servidor ja rodando na porta 8000.
) else (
    echo [1/3] Iniciando php artisan serve...
    start "FinFamilia-Server" cmd /k "cd /d %~dp0.. && php artisan serve --host=0.0.0.0 --port=8000"
    powershell -NoProfile -Command "Start-Sleep -Seconds 6" >nul
)

REM 2) Localizar o cloudflared
set "CLOUD="
if exist "%ProgramFiles(x86)%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles(x86)%\cloudflared\cloudflared.exe"
if "%CLOUD%"=="" if exist "%ProgramFiles%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles%\cloudflared\cloudflared.exe"
if "%CLOUD%"=="" for /f "delims=" %%i in ('where cloudflared 2^>nul') do if not defined CLOUD set "CLOUD=%%i"
if "%CLOUD%"=="" (
    echo ERRO: cloudflared nao encontrado. Instale com:
    echo    winget install Cloudflare.cloudflared
    pause
    exit /b 1
)

REM 3) Tunel
if exist "%~dp0..\storage\logs\cloudflared.log" del "%~dp0..\storage\logs\cloudflared.log"
echo [2/3] Iniciando cloudflared (aguarde a URL)...
start "FinFamilia-Tunel" cmd /k ""%CLOUD%" tunnel --url http://localhost:8000 --loglevel warn --logfile "%~dp0..\storage\logs\cloudflared.log""

echo Aguardando URL publica...
set "URL="
for /l %%i in (1,1,40) do (
    if not defined URL (
        for /f "usebackq delims=" %%u in (`powershell -NoProfile -Command "(Select-String -Path '%~dp0..\storage\logs\cloudflared.log' -Pattern 'https://[a-z0-9.-]+\.trycloudflare\.com' -AllMatches).Matches.Value ^| Select-Object -First 1"`) do set "URL=%%u"
    )
    if not defined URL powershell -NoProfile -Command "Start-Sleep -Seconds 1" >nul
)
if "%URL%"=="" (
    echo Nao consegui obter a URL do tunel. Veja storage\logs\cloudflared.log
    pause
    exit /b 1
)

echo URL do tunel: %URL%
echo [3/3] Registrando webhook...
php artisan bot:telegram-webhook "%URL%/api/bot/telegram"

echo.
echo Bot no ar. Teste no celular o @finfamiliaapp_bot.
echo Para parar, feche as janelas "FinFamilia-Tunel" e "FinFamilia-Server".
pause