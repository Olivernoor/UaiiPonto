# 🚀 Guia Rápido - Integração Frontend + Backend

## ✅ O que foi feito

- ✅ `conector.js` atualizado para fazer requisições reais à API
- ✅ `apiConfig.js` configurado com endpoints corretos
- ✅ `login.html` e `cadastro.html` importando os scripts
- ✅ Funções de login/registro chamando o backend via HTTP

## 📋 Pré-requisitos

- PHP 8.0+
- Composer instalado
- Node.js ou servidor PHP local

## 🏃 Como rodar em 3 passos

### Terminal 1 - Backend (Laravel)

```bash
cd UaiiPonto/Backend
composer install  # (primeira vez apenas)
php artisan migrate  # (primeira vez apenas)
php artisan serve
# Rodará em http://localhost:8000
```

### Terminal 2 - Frontend (PHP Server)

```bash
cd UaiiPonto/Frontend
php -S localhost:3000
# Rodará em http://localhost:3000
```

### Terminal 3 - Testes (Optional)

```bash
cd UaiiPonto
# Use a Postman collection ou curl
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha123",
    "organization": "Minha Empresa"
  }'
```

## 🧪 Testando no navegador

1. **Abra** http://localhost:3000/pages/login.html
2. **Cadastro**: Clique em "Já tem conta? Login" → Link para cadastro
3. **Preencha os dados** e clique em "Concluir"
4. **Veja a resposta** no console (F12 → Console)
5. **Se sucesso**: Será redirecionado para login
6. **Faça login** com as credenciais criadas

## 🔍 Verificar se tudo funciona

Abra o navegador em `http://localhost:3000/pages/login.html` e veja:

- ✅ **Console do navegador** (F12 → Console)
  - Verá logs de requisição
  - Erros CORS aparecerão aqui
  
- ✅ **Network tab** (F12 → Network)
  - Veja as requisições POST/GET
  - Status 200 = sucesso
  - Status 401/403 = erro de autenticação
  - Status 500 = erro do servidor

## 🐛 Troubleshooting

### "CORS error" - Erro de Acesso

**Problema**: `Access to XMLHttpRequest blocked by CORS`

**Solução**: 
- Certificar-se que backend está rodando em `localhost:8000`
- Verificar `config/cors.php` no backend

### "Backend não responde"

**Verificar**:
```bash
# Backend rodando?
curl http://localhost:8000/
# Deve retornar: {"message": "UaiiPonto API - Rodando"}
```

### Credenciais não funcionam

**Verificar**:
1. Se o usuário foi realmente cadastrado (check database)
2. Se há erro na resposta do servidor (check Network tab)
3. Limpar localStorage: `localStorage.clear()` no console

## 📱 URLs principais

| Página | URL |
|--------|-----|
| Login | http://localhost:3000/pages/login.html |
| Cadastro | http://localhost:3000/pages/cadastro.html |
| Dashboard | http://localhost:3000/pages/dashboard.html |
| Backend | http://localhost:8000 |
| Status API | http://localhost:8000/ |

## 🔗 API Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/register` | Criar novo usuário |
| POST | `/login` | Fazer login |
| POST | `/logout` | Logout (precisa token) |
| GET | `/me` | Dados do usuário (precisa token) |
| PUT | `/users/{id}` | Atualizar usuário |

## 💾 Arquivos modificados

- `Frontend/js/conector.js` - Agora usa API real
- `Frontend/js/apiConfig.js` - Configuração centralizada
- `Frontend/pages/login.html` - Importações corretas
- `Frontend/pages/cadastro.html` - Importações corretas

## ✨ Próximos passos

Quando tudo funcionar:
1. Integrar Dashboard com dados reais da API
2. Implementar batida de ponto (check-in/check-out)
3. Adicionar relatórios
4. Implementar autenticação persistente

---

**Qualquer dúvida, verifique o console do navegador (F12) para ver mensagens de erro detalhadas!**
