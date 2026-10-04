@echo off
REM Prumo - sobe a stack exposta na rede local (LAN).
REM Detecta o IPv4 da maquina, ajusta APP_URL no .env.docker e roda o compose.
setlocal EnableDelayedExpansion
cd /d "%~dp0.."

if not exist .env.docker (
    echo [ERRO] .env.docker nao encontrado. Rode: copy .env.docker.example .env.docker
    exit /b 1
)

echo Detectando IP da rede local...
set LANIP=
for /f "delims=" %%i in ('powershell -NoProfile -ExecutionPolicy Bypass -Command "[System.Net.Dns]::GetHostAddresses([System.Net.Dns]::GetHostName()) ^| Where-Object { $_.AddressFamily -eq 'InterNetwork' -and $_.ToString() -notlike '127.*' -and $_.ToString() -notlike '169.254.*' } ^| Select-Object -First 1 -ExpandProperty IPAddressToString" 2^>nul') do set LANIP=%%i

REM Fallback: parse do ipconfig (primeiro IPv4 valido nao-loopback).
if "!LANIP!"=="" (
    for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4" 2^>nul') do (
        set ip=%%a
        set ip=!ip: =!
        set ip=!ip:	=!
        if "!ip!"=="!ip:127.=!" if "!ip!"=="!ip:169.254.=!" (
            echo !ip! | findstr /R "^[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*$" >nul
            if not errorlevel 1 if "!LANIP!"=="" set LANIP=!ip!
        )
    )
)

echo !LANIP! | findstr /R "^[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*$" >nul
if errorlevel 1 (
    echo [ERRO] Nao detectei o IP local (saiu vazio: "!LANIP!"). Conecte-se ao Wi-Fi e rode como administrador.
    exit /b 1
)
echo IP local: !LANIP!

powershell -NoProfile -ExecutionPolicy Bypass -Command "(Get-Content .env.docker) -replace '^APP_URL=.*', 'APP_URL=http://!LANIP!:8080' | Set-Content .env.docker"
echo APP_URL ajustado para http://!LANIP!:8080

echo Liberando porta 8080 no firewall do Windows (se possivel)...
netsh advfirewall firewall delete rule name="Prumo App 8080" >nul 2>&1
netsh advfirewall firewall add rule name="Prumo App 8080" dir=in action=allow protocol=TCP localport=8080 >nul 2>&1
if errorlevel 1 (
    echo [AVISO] Sem permissao de administrador: libere a porta 8080 manualmente se nao abrir no celular.
) else (
    echo Firewall liberado para a porta 8080.
)

echo Derrubando containers antigos (SEM -v: preserva banco e volumes)...
docker compose --env-file .env.docker down
echo Subindo containers (build pode demorar na primeira vez)...
docker compose --env-file .env.docker up -d --build
if errorlevel 1 exit /b 1

echo Aguardando o app responder (ate ~3 min na primeira subida)...
set OK=0
for /L %%t in (1,1,36) do (
    curl -s -o nul -m 5 http://localhost:8080/login >nul 2>&1
    if not errorlevel 1 set OK=1
    if !OK!==1 goto :up
    timeout /t 5 /nobreak >nul
)
echo [AVISO] O app ainda nao respondeu. Veja: docker compose --env-file .env.docker logs prumo-app prumo-nginx prumo-db
goto :ports

:up
echo App respondendo.
docker compose --env-file .env.docker exec prumo-app php artisan migrate --force >nul 2>&1

:ports
echo.
docker compose --env-file .env.docker ps
echo.
echo ============================================
echo  Prumo no ar na rede local:
echo    App:        http://!LANIP!:8080
echo    phpMyAdmin: http://!LANIP!:8081
echo ============================================
echo Nada deve rodar na porta 8000 (era o 'php artisan serve' antigo).
echo No celular (mesmo Wi-Fi), abra o IP acima.
endlocal
