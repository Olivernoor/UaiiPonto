# 🧪 Testes Rápidos - API Integration

## ✅ Pre-requisitos

1. Backend rodando em `http://localhost:8000`
2. Frontend rodando em `http://localhost:3000`
3. `curl` instalado no terminal ou usar Postman

## 🚀 Testes via cURL

### 1. Verificar se Backend está respondendo

```bash
curl -X GET http://localhost:8000/
```

**Esperado:**
```json
{"message": "UaiiPonto API - Rodando"}
```

---

### 2. Registrar um novo usuário

```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha123",
    "organization": "Minha Empresa"
  }'
```

**Esperado (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "organization": "Minha Empresa",
    "role": "user"
  }
}
```

---

### 3. Fazer Login

```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "senha123"
  }'
```

**Esperado (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "organization": "Minha Empresa",
    "role": "user"
  }
}
```

**Guarde o token para usar nos próximos testes:**
```bash
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

### 4. Obter dados do usuário logado

```bash
curl -X GET http://localhost:8000/me \
  -H "Authorization: Bearer $TOKEN"
```

**Esperado (200 OK):**
```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@example.com",
  "organization": "Minha Empresa",
  "role": "user"
}
```

---

### 5. Atualizar dados do usuário

```bash
curl -X PUT http://localhost:8000/users/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "João da Silva"
  }'
```

**Esperado (200 OK):**
```json
{
  "message": "Usuário atualizado com sucesso",
  "user": {
    "id": 1,
    "name": "João da Silva",
    "email": "joao@example.com"
  }
}
```

---

### 6. Fazer Logout

```bash
curl -X POST http://localhost:8000/logout \
  -H "Authorization: Bearer $TOKEN"
```

**Esperado (200 OK):**
```json
{"message": "Logged out successfully"}
```

---

## 🌐 Testes via Navegador

### 1. Cadastro

1. Abra http://localhost:3000/pages/cadastro.html
2. Preencha:
   - Nome: seu nome
   - E-mail: seu@email.com
   - Organização: sua empresa
   - Senha: senha123
   - Confirmar: senha123
3. Clique em "Concluir"
4. Verifique o console (F12) para ver a requisição

### 2. Login

1. Abra http://localhost:3000/pages/login.html
2. Use as credenciais que criou
3. Se sucesso, será redirecionado para dashboard.html

---

## 🔍 Depuração no Navegador

Abra o Dev Tools (F12) e vá para:

### Network Tab
- Veja todas as requisições HTTP
- Clique em uma requisição (ex: POST /login)
- Veja Response e Preview
- Status 200-299 = sucesso
- Status 400-499 = erro do cliente
- Status 500 = erro do servidor

### Console Tab
- Veja logs do JavaScript
- Erros de CORS aparecem em vermelho
- Mensagens do `console.log()` aparecem aqui

### Application Tab
- Veja localStorage
- Procure por: `token` e `user`

---

## ❌ Erros Comuns

### "CORS error"

Significa que o Backend não está permitindo requisições do Frontend.

**Verificar:**
```bash
curl -X OPTIONS http://localhost:8000/ -v
```

Deve retornar headers com `Access-Control-Allow-Origin`.

**Solução:**
- Certificar-se que `config/cors.php` está configurado
- Que `bootstrap/app.php` registra o middleware CORS
- Que Backend foi reiniciado após alterações

---

### "Connection refused"

Significa que o Backend não está rodando.

**Verificar:**
```bash
# No terminal do Backend, deve aparecer:
# Laravel development server started: http://127.0.0.1:8000
```

---

### "401 Unauthorized"

Significa que o token é inválido ou não foi enviado.

**Verificar:**
- Que copiou o token corretamente (sem aspas)
- Que incluiu `Authorization: Bearer $TOKEN` no header

---

### "Credenciais inválidas"

Significa que email/senha não conferem.

**Verificar:**
1. Cadastrou o usuário com sucesso (veja console)
2. Que está usando o email e senha corretos
3. Que a senha tem no mínimo 6 caracteres

---

## 📊 Fluxo de Teste Completo

```
1. Backend rodando?     → GET http://localhost:8000/
2. Frontend rodando?    → Abrir http://localhost:3000/pages/login.html
3. Cadastro funciona?   → Preencher cadastro, vê sucesso no console?
4. Login funciona?      → Fazer login com credenciais criadas
5. API protegida?       → GET /me deve retornar dados do usuário
6. Logout funciona?     → POST /logout com token
```

---

## 💡 Dicas

- Use `curl -v` para ver headers e response completo
- Use Postman/Insomnia para testes mais fáceis (GUI)
- Teste no navegador depois de testar via cURL
- Limpe localStorage (`localStorage.clear()`) se tiver problemas
- Verifique o arquivo `requests.http` no Backend para exemplos

---

**Qualquer dúvida, abra o console do navegador (F12) e veja a mensagem de erro!**
