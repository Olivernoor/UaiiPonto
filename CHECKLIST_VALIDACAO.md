# ✅ Checklist de Validação - Integração Frontend + Backend

Use este checklist para verificar se tudo está funcionando corretamente!

---

## 📋 Pré-requisitos

- [ ] PHP 8.0+ instalado
- [ ] Composer instalado
- [ ] MySQL/PostgreSQL rodando (ou SQLite)
- [ ] Node.js + npm (opcional, para ferramentas extras)
- [ ] Browser moderno (Chrome, Firefox, Safari, Edge)

---

## 🔧 Setup Inicial

### Backend

- [ ] Executar: `cd Backend && composer install`
- [ ] Executar: `cp .env.example .env`
- [ ] Executar: `php artisan key:generate`
- [ ] Executar: `php artisan migrate`
- [ ] Executar: `php artisan serve`
- [ ] Verificar: Backend respondendo em `http://localhost:8000/`

### Frontend

- [ ] Arquivos JavaScript carregados:
  - [ ] `apiConfig.js` (configuração da API)
  - [ ] `conector.js` (manipuladores de formulário)
- [ ] Arquivos importados em `login.html` e `cadastro.html`
- [ ] Executar: `cd Frontend && php -S localhost:3000`
- [ ] Verificar: Frontend acessível em `http://localhost:3000/pages/login.html`

---

## 🧪 Teste 1: Backend Respondendo

### Checklist

- [ ] Terminal Backend rodando sem erros
- [ ] Executar no terminal: `curl http://localhost:8000/`
- [ ] Resposta JSON com mensagem "UaiiPonto API - Rodando"

### Se falhar:
- [ ] Verificar se porta 8000 está em uso: `netstat -an | grep 8000`
- [ ] Matar processo: `lsof -i :8000` (Mac/Linux) ou `netstat -ano | findstr :8000` (Windows)
- [ ] Tentar porta diferente: `php artisan serve --port=8080`

---

## 🧪 Teste 2: Registro de Usuário

### Via Navegador

1. [ ] Abrir: `http://localhost:3000/pages/login.html`
2. [ ] Clicar em "Já tem conta? Login" (deve ir para cadastro)
3. [ ] Preencher formulário:
   - [ ] Nome: "Teste User"
   - [ ] Email: "teste@example.com"
   - [ ] Organização: "Teste Org"
   - [ ] Senha: "senha123"
   - [ ] Confirmar: "senha123"
4. [ ] Clicar em "Concluir"
5. [ ] Abrir console (F12)
   - [ ] Verificar aba "Network"
   - [ ] Deve haver requisição `POST /register` com status `200` ou `201`
   - [ ] Response deve conter: `token`, `user.id`, `user.name`, etc.
6. [ ] Deve exibir mensagem de sucesso
7. [ ] Deve redirecionar para `login.html` após 2 segundos

### Via cURL (alternativo)

```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste User",
    "email": "teste@example.com",
    "password": "senha123",
    "organization": "Teste Org"
  }'
```

- [ ] Resposta com status 200
- [ ] Response contém `token` e `user`

### Se falhar:
- [ ] Email já cadastrado? Usar email diferente
- [ ] Senha < 6 caracteres? Usar senha maior
- [ ] Erro "CORS"? Verificar `config/cors.php` no Backend
- [ ] Erro "500"? Ver logs do Backend

---

## 🧪 Teste 3: Login

### Via Navegador

1. [ ] Abrir: `http://localhost:3000/pages/login.html`
2. [ ] Preencher:
   - [ ] Email: "teste@example.com"
   - [ ] Senha: "senha123"
3. [ ] Clicar em "Login"
4. [ ] Abrir console (F12)
   - [ ] Verificar aba "Network"
   - [ ] Deve haver requisição `POST /login` com status `200`
5. [ ] Deve exibir "Login realizado com sucesso!"
6. [ ] Deve redirecionar para `dashboard.html` após 1.5 segundos

### Verificar LocalStorage

```javascript
// No console do navegador, executar:
localStorage.getItem('token')
localStorage.getItem('user')
```

- [ ] `token` é uma string não-vazia
- [ ] `user` é um JSON com: `name`, `email`, `organization`, `role`

### Via cURL (alternativo)

```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@example.com",
    "password": "senha123"
  }'
```

- [ ] Resposta com status 200
- [ ] Response contém `token` e `user`

### Se falhar:
- [ ] Email ou senha inválidos? Tentar novamente
- [ ] Erro "CORS"? Verificar configuração no Backend
- [ ] Token não salvando em localStorage? Verificar console

---

## 🧪 Teste 4: Dados do Usuário Logado

### Via cURL (requer token)

```bash
# 1. Fazer login e copiar o token
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

# 2. Executar
curl -X GET http://localhost:8000/me \
  -H "Authorization: Bearer $TOKEN"
```

- [ ] Resposta com status 200
- [ ] Response contém: `id`, `name`, `email`, `organization`, `role`

### Via Navegador Console

```javascript
// Executar no console após fazer login:
fetch('http://localhost:8000/me', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
})
.then(r => r.json())
.then(d => console.log(d))
```

- [ ] Deve exibir dados do usuário em JSON

### Se falhar:
- [ ] Token expirou? Fazer login novamente
- [ ] Header Authorization não enviado? Verificar apiConfig.js
- [ ] Erro "401 Unauthorized"? Token inválido

---

## 🧪 Teste 5: Logout

### Via Navegador (se dashboard.html tiver botão de logout)

1. [ ] Clicar botão "Logout"
2. [ ] Verificar Network (deve ter `POST /logout`)
3. [ ] Deve redirecionar para `login.html`
4. [ ] LocalStorage deve estar vazio

### Via cURL

```bash
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -X POST http://localhost:8000/logout \
  -H "Authorization: Bearer $TOKEN"
```

- [ ] Resposta com status 200
- [ ] Message: "Logged out successfully"

---

## 🧪 Teste 6: Validações do Frontend

### Email Inválido

- [ ] Tentar cadastro com email vazio
- [ ] Deve exibir erro: "Por favor, preencha todos os campos!"

### Senhas Não Conferem

- [ ] Cadastro com senhas diferentes
- [ ] Deve exibir erro: "As senhas não conferem!"

### Senha Muito Curta

- [ ] Cadastro com senha < 6 caracteres
- [ ] Deve exibir erro: "A senha deve ter no mínimo 6 caracteres!"

### Email Duplicado

1. [ ] Criar usuário "user1@test.com"
2. [ ] Tentar criar outro com mesmo email
3. [ ] Deve retornar erro do Backend (API)

---

## 🧪 Teste 7: CORS (Cross-Origin)

### Verificar Headers CORS

```bash
curl -X OPTIONS http://localhost:8000/ -v | grep -i "access-control"
```

Deve retornar:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS
Access-Control-Allow-Headers: ...
```

- [ ] Headers CORS presentes
- [ ] Origens permitidas incluem `http://localhost:3000`

### Se falhar:
- [ ] Verificar `config/cors.php`
- [ ] Verificar `bootstrap/app.php` (middleware registrado)
- [ ] Reiniciar Backend: `php artisan serve`

---

## 📊 Testes Avançados (Opcional)

### Teste de Performance

- [ ] Requisição de login < 1 segundo
- [ ] Requisição GET /me < 500ms
- [ ] Sem memory leaks no navegador

### Teste de Segurança

- [ ] Token não expõe informações sensíveis (JWT)
- [ ] Senha enviada via HTTPS (em produção)
- [ ] CORS não permite acesso de sites maliciosos

### Teste de Banco de Dados

```bash
# Verificar se tabelas foram criadas
sqlite3 database.sqlite ".tables"
# Ou para MySQL:
mysql -u root -p -e "USE uaiiponto_db; SHOW TABLES;"
```

- [ ] Tabelas `users`, `time_entries`, `reports` existem

---

## ✅ Checklist Final

- [ ] Backend rodando em `http://localhost:8000`
- [ ] Frontend rodando em `http://localhost:3000`
- [ ] Registro funciona
- [ ] Login funciona
- [ ] Token é salvo em localStorage
- [ ] `/me` endpoint retorna dados do usuário
- [ ] Logout limpa localStorage
- [ ] Sem erros CORS no console
- [ ] Sem erros 500 no Backend
- [ ] Validações do frontend funcionam
- [ ] Mensagens de feedback aparecem ao usuário

---

## 🎯 Resultado Esperado

Se todos os testes passarem (✅), sua integração está **100% funcional**!

```
✅ Frontend comunicando com Backend
✅ Autenticação (registro + login) funcionando
✅ Token JWT sendo salvado e usado
✅ CORS configurado corretamente
✅ Validações no frontend e backend
✅ Sistema pronto para próximas fases
```

---

## 🚀 Próximos Passos

1. Integrar Dashboard com `/me` endpoint
2. Implementar Batida de Ponto (check-in/check-out)
3. Criar página de Relatórios
4. Adicionar exportação PDF/Excel
5. Sistema de Aprovação de Batidas por Gestores

---

## 💡 Dicas Úteis

### Limpar Cache do Navegador
```javascript
// No console:
localStorage.clear()
sessionStorage.clear()
// Depois recarregar a página: F5 ou Ctrl+R
```

### Ver Logs do Backend
```bash
# No terminal do Backend, deve aparecer:
# [timestamp] "POST /login HTTP/1.1" 200
# [timestamp] "GET /me HTTP/1.1" 200
```

### Debug de Requisições
Abra DevTools (F12) → Network → Clique na requisição → Response/Preview

### Reset de Banco de Dados
```bash
cd Backend
php artisan migrate:fresh --seed
```

---

**Status:** Integração Completa  
**Última atualização:** 20 de Abril de 2026  
**Próxima fase:** Dashboard e Batida de Ponto
