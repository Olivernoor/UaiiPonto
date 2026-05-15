# Script de inicialização rápida - UaiiPonto Frontend + Backend
# Uso: No PowerShell (como administrador), execute: .\iniciar.ps1

Write-Host "
═══════════════════════════════════════════════════
   UaiiPonto - Iniciador Rápido (PowerShell)
═══════════════════════════════════════════════════
" -ForegroundColor Cyan

$BasePath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Verificar se está no diretório correto
if (-not (Test-Path "$BasePath\Backend")) {
    Write-Host "❌ Erro: Executar este script na raiz do projeto (UaiiPonto/)" -ForegroundColor Red
    Read-Host "Pressione Enter para fechar"
    exit 1
}

Write-Host "[1/3] Iniciando Backend (Laravel)..." -ForegroundColor Yellow
$BackendPath = "$BasePath\Backend"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$BackendPath'; php artisan serve" -WindowStyle Normal

Write-Host "[2/3] Aguardando Backend iniciar..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

Write-Host "[3/3] Iniciando Frontend (PHP Server)..." -ForegroundColor Yellow
$FrontendPath = "$BasePath\Frontend"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$FrontendPath'; php -S localhost:3000" -WindowStyle Normal

Write-Host "
═══════════════════════════════════════════════════
   ✓ Inicialização completa!
═══════════════════════════════════════════════════

URLs para acessar:
  Frontend:  http://localhost:3000/pages/login.html
  Backend:   http://localhost:8000

Teste:
  1. Abra http://localhost:3000/pages/login.html
  2. Clique em 'Já tem conta? Login' para ir para cadastro
  3. Preencha os dados e clique em 'Concluir'
  4. Você deve ser redirecionado para login
  5. Faça login com as credenciais criadas

Troubleshooting:
  • Verifique o console do navegador (F12) para erros
  • Verifique a aba Network no DevTools
  • Veja GUIA_INTEGRACAO_RAPIDO.md para mais detalhes

" -ForegroundColor Green

Read-Host "Pressione Enter para fechar este terminal"
