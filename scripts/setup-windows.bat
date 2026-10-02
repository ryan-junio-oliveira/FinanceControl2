@echo off
setlocal
REM Prumo — setup automático no Windows.
REM Uso: duplo clique em scripts\setup-windows.bat (ou via terminal).
REM O script sobe um diretorio (raiz do projeto) e configura tudo.
REM O Tesseract ja vem portatil em bin\tesseract (sem instalar nada).

REM Sobe para a raiz do projeto (este script mora em scripts\).
cd /d "%~dp0.."

echo ==^> 1/5 Verificando pre-requisitos...
where php >nul 2>nul || (echo [ERRO] PHP nao encontrado no PATH. Instale o PHP 8.3+ e tente de novo. & pause & exit /b 1)
where composer >nul 2>nul || (echo [ERRO] Composer nao encontrado no PATH. Instale em getcomposer.org. & pause & exit /b 1)
where node >nul 2>nul || (echo [ERRO] Node nao encontrado no PATH. Instale o Node 20 LTS. & pause & exit /b 1)
php -r "exit(version_compare(PHP_VERSION, '8.3.0', '<') ? 1 : 0);" || (echo [ERRO] PHP 8.3+ necessario. & pause & exit /b 1)
echo     [ok] PHP, Composer e Node encontrados.

echo ==^> 2/5 Instalando dependencias PHP...
call composer install || (pause & exit /b 1)

echo ==^> 3/5 Configurando ambiente (.env)...
if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo     [ok] .env criado a partir do exemplo.
)
call php artisan key:generate --force >nul

echo ==^> 4/5 Banco de dados (migrations)...
call php artisan migrate --force || (pause & exit /b 1)

echo ==^> 5/5 Frontend (npm + build)...
call npm install --no-audit --no-fund || (pause & exit /b 1)
call npm run build || (pause & exit /b 1)

echo.
echo Pronto! Para rodar:
echo   php artisan serve --host=0.0.0.0 --port=8000
echo   (OCR usa o Tesseract portatil em bin\tesseract — nada a instalar.)
pause