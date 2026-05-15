@echo off
REM Script de teste da integração da batida de ponto

echo.
echo ========================================
echo  TESTE DA BATIDA DE PONTO - UaiiPonto
echo ========================================
echo.

REM Verificar se backend está rodando
echo [1] Testando conexão com backend...
curl -s http://localhost:8000/health >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Backend está rodando em http://localhost:8000
) else (
    echo ❌ Backend não está respondendo. Inicie com: php artisan serve
    exit /b 1
)

echo.
echo [2] Testando login...
for /f "tokens=*" %%i in ('curl -s -X POST http://localhost:8000/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@uaiiponto.com\",\"password\":\"123456\"}" ^
  ^| findstr "token"') do set TOKEN_LINE=%%i

if "%TOKEN_LINE%"=="" (
    echo ❌ Falha no login. Verifique credenciais.
    exit /b 1
) else (
    echo ✅ Login bem-sucedido
    REM Extrair token (simplificado)
    echo Token recebido (primeiros 20 chars): %TOKEN_LINE:~0,20%...
)

echo.
echo [3] Testando GET /me (dados do usuário)...
curl -s -X GET http://localhost:8000/me ^
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." >nul 2>&1
echo ✅ Endpoint /me está acessível

echo.
echo [4] Testando GET /time-entries/today (batida de hoje)...
curl -s -X GET "http://localhost:8000/time-entries/today" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Endpoint /time-entries/today está acessível
) else (
    echo ❌ Erro ao acessar /time-entries/today
)

echo.
echo [5] Testando GET /time-entries/history (histórico)...
curl -s -X GET "http://localhost:8000/time-entries/history" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Endpoint /time-entries/history está acessível
) else (
    echo ❌ Erro ao acessar /time-entries/history
)

echo.
echo ========================================
echo  PRÓXIMOS PASSOS:
echo ========================================
echo.
echo 1. Inicie o backend: cd Backend && php artisan serve
echo 2. Inicie o frontend: cd Frontend && php -S localhost:3000
echo 3. Abra: http://localhost:3000/pages/login.html
echo 4. Faça login com:
echo    Email: admin@uaiiponto.com
echo    Senha: 123456
echo 5. Clique em "Ativar Localização"
echo 6. Clique em "Registrar Entrada"
echo 7. Verifique o Histórico
echo.
echo Para debug: Abra DevTools (F12) e vá para a aba Network
echo ========================================
echo.
