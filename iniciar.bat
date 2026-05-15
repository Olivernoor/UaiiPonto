@echo off
REM Script de inicialização rápida - UaiiPonto Frontend + Backend
REM Uso: Colocar este arquivo na raiz do projeto (UaiiPonto/) e executar

echo.
echo ===============================================
echo   UaiiPonto - Iniciador Rápido
echo ===============================================
echo.

REM Obter caminho base
set BASE_PATH=%~dp0

REM Verificar se está no diretório correto
if not exist "%BASE_PATH%Backend" (
    echo Erro: Executar este script na raiz do projeto (UaiiPonto/)
    pause
    exit /b 1
)

echo [1/3] Iniciando Backend (Laravel)...
echo.
echo.
start "Backend - UaiiPonto" cmd /k "cd /d "%BASE_PATH%Backend" && php artisan serve"

echo [2/3] Aguardando Backend iniciar (3 segundos)...
timeout /t 3 /nobreak

echo [3/3] Iniciando Frontend (PHP Server)...
echo.
start "Frontend - UaiiPonto" cmd /k "cd /d "%BASE_PATH%Frontend" && php -S localhost:3000"

echo.
echo ===============================================
echo   ✓ Inicialização completa!
echo ===============================================
echo.
echo URLs para acessar:
echo   Frontend:  http://localhost:3000/pages/login.html
echo   Backend:   http://localhost:8000
echo.
echo Verifique o console do navegador (F12) para erros!
echo.
pause
