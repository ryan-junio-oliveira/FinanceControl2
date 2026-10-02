@echo off
setlocal
REM Prumo - instala o tunel Cloudflare e o auto-start no Windows.
REM Rode UMA VEZ como Administrador: scripts\tunnel-install-windows.bat [TOKEN-DO-TUNEL]
REM
REM Antes, no painel da Cloudflare em Zero Trust, Networks, Tunnels:
REM   1. Create a tunnel, Cloudflared, nome: prumo
REM   2. Copie o TOKEN de instalacao da aba Windows
REM   3. Em Public Hostname, adicione seu dominio apontando para http://localhost:8000
REM      Exemplo: app.seudominio.com.br. Sem dominio proprio nao ha URL fixa.
REM   4. Cole o token aqui ou passe como argumento, e preencha TUNNEL_URL no .env.

REM Sobe um diretorio: a raiz do projeto.
cd /d "%~dp0.."
set "ROOT=%CD%"

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERRO] Rode como Administrador. Botao direito, Executar como administrador.
    pause
    exit /b 1
)

REM Localiza o cloudflared.
set "CLOUD="
if exist "%ProgramFiles(x86)%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles(x86)%\cloudflared\cloudflared.exe"
if "%CLOUD%"=="" if exist "%ProgramFiles%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles%\cloudflared\cloudflared.exe"
if "%CLOUD%"=="" (
    echo Instalando cloudflared via winget...
    winget install --silent --accept-source-agreements --accept-package-agreements Cloudflare.cloudflared
    if exist "%ProgramFiles%\cloudflared\cloudflared.exe" set "CLOUD=%ProgramFiles%\cloudflared\cloudflared.exe"
)
if "%CLOUD%"=="" (
    echo [ERRO] cloudflared nao encontrado. Instale manualmente e rode de novo.
    pause
    exit /b 1
)
echo [ok] cloudflared localizado.

REM Token do tunel via argumento ou pergunta.
set "TOKEN=%~1"
if "%TOKEN%"=="" set /p "TOKEN=Cole o TOKEN do tunel: "
if "%TOKEN%"=="" (
    echo [ERRO] Token vazio.
    pause
    exit /b 1
)

REM Caminho curto sem espacos para a tarefa agendada.
for %%I in ("%CLOUD%") do set "CLOUDS=%%~sI"

REM Tarefa do tunel: logon e boot, reconecta sozinho.
schtasks /create /tn "PrumoTunnel" /f /sc onlogon /rl highest /tr "%CLOUDS% tunnel --no-autoupdate run --token %TOKEN%" >nul
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao criar a tarefa PrumoTunnel.
    pause
    exit /b 1
)
echo [ok] Tarefa PrumoTunnel criada. Inicia junto com o Windows.

REM Tarefa da pilha: servidor + webhook, 30s apos logon.
schtasks /create /tn "PrumoStack" /f /sc onlogon /delay 0000:30 /tr "cmd /c %ROOT%\scripts\start-stack.bat" >nul
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao criar a tarefa PrumoStack.
    pause
    exit /b 1
)
echo [ok] Tarefa PrumoStack criada. Sobe servidor e registra webhook.

echo.
echo Proximos passos, uma vez so:
echo   1. No .env, preencha TUNNEL_URL com seu dominio, exemplo https://app.seudominio.com.br
echo   2. No Telegram e no MP, aponte os webhooks para essa URL fixa.
echo   3. Reinicie o PC para validar: tudo volta sozinho.
echo.
echo Para testar agora sem reiniciar, rode: scripts\start-stack.bat
pause
