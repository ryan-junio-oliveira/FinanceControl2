@echo off
setlocal
REM Prumo - para a pilha completa: servidor Laravel + tunel cloudflared.
REM As tarefas agendadas de boot sao mantidas para o proximo logon.
REM Uso: scripts\stop-stack.bat

REM Sobe um diretorio: a raiz do projeto.
cd /d "%~dp0.."
set "ROOT=%CD%"
set "LOG=%ROOT%\storage\logs\stack.log"

echo [%date% %time%] stop-stack >> "%LOG%"

REM 1 - Servidor na porta 8000: mata o PID que estiver escutando.
for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%p >nul 2>&1
)
echo [1/2] Servidor parado.
taskkill /FI "WINDOWTITLE eq Prumo-Server*" >nul 2>&1

REM 2 - Tunel cloudflared: para o servico se existir e mata processos.
sc stop cloudflared >nul 2>&1
taskkill /F /IM cloudflared.exe >nul 2>&1
echo [2/2] Tunel parado.
taskkill /FI "WINDOWTITLE eq Prumo-Tunel*" >nul 2>&1

echo Pilha parada. Para subir de novo: scripts\start-stack.bat
echo stop ok >> "%LOG%"
