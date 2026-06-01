# Print3D Web API

API REST desenvolvida em Laravel para gerenciamento de clientes, pedidos e orçamentos para serviços de impressão 3D.

---

## Tecnologias

- PHP 8+
- Laravel 12
- Laravel Sanctum
- MySQL/PostgreSQL
- REST API

---

## Funcionalidades

### Autenticação

- Cadastro de usuários
- Login
- Logout
- Consulta do usuário autenticado

### Clientes

- Criar cliente
- Listar clientes
- Atualizar cliente
- Excluir cliente

### Pedidos

- Criar pedido
- Listar pedidos
- Atualizar pedido
- Excluir pedido
- Alterar status
- Upload de imagem

### Aprovação Pública

- Visualização de orçamento por token
- Aprovação de orçamento
- Recusa de orçamento

---

# Instalação

## Clonar o projeto

```bash
git clone https://github.com/seu-usuario/print3d-web-api.git
```

## Entrar na pasta

```bash
cd print3d-web-api
```

## Instalar dependências

```bash
composer install
```

## Copiar arquivo de ambiente

```bash
cp .env.example .env
```

## Gerar chave da aplicação

```bash
php artisan key:generate
```

## Configurar banco de dados

Edite o arquivo `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=print3d
DB_USERNAME=root
DB_PASSWORD=
```

## Executar migrations

```bash
php artisan migrate
```

## Criar link do storage

```bash
php artisan storage:link
```

## Iniciar servidor

```bash
php artisan serve
```

---

# Autenticação

## Registrar usuário

### Endpoint

```http
POST /api/register
```

### Body

```json
{
    "name": "João Silva",
    "email": "joao@email.com",
    "password": "12345678",
    "password_confirmation": "12345678"
}
```

### Resposta

```json
{
    "user": {},
    "token": "TOKEN"
}
```

---

## Login

### Endpoint

```http
POST /api/login
```

### Body

```json
{
    "email": "joao@email.com",
    "password": "12345678"
}
```

### Resposta

```json
{
    "token": "TOKEN",
    "user": {}
}
```

---

## Logout

### Endpoint

```http
POST /api/logout
```

### Headers

```http
Authorization: Bearer TOKEN
```

---

# Clientes

## Listar clientes

```http
GET /api/clients
```

## Criar cliente

```http
POST /api/clients
```

### Body

```json
{
    "name": "Empresa XPTO",
    "email": "contato@empresa.com",
    "phone": "(11)99999-9999"
}
```

## Atualizar cliente

```http
PUT /api/clients/{id}
```

## Remover cliente

```http
DELETE /api/clients/{id}
```

---

# Pedidos

## Listar pedidos

```http
GET /api/orders
```

## Buscar pedido

```http
GET /api/orders/{id}
```

## Criar pedido

```http
POST /api/orders
```

### FormData

```text
client_id
title
description
price
deadline
reference_image
```

## Atualizar pedido

```http
PUT /api/orders/{id}
```

## Excluir pedido

```http
DELETE /api/orders/{id}
```

## Atualizar status

```http
PATCH /api/orders/{id}/status
```

### Body

```json
{
    "status": "printing"
}
```

---

# Status Disponíveis

```text
budget
approved
printing
done
delivered
rejected
```

Fluxo:

```text
budget
  ↓
approved
  ↓
printing
  ↓
done
  ↓
delivered
```

ou

```text
budget
  ↓
rejected
```

---

# Rotas Públicas

## Visualizar orçamento

```http
GET /api/public/orders/{token}
```

## Aprovar orçamento

```http
PATCH /api/public/orders/{token}/approve
```

## Recusar orçamento

```http
PATCH /api/public/orders/{token}/reject
```

---

# Middleware de Assinatura

A API possui um middleware responsável por verificar se o usuário possui assinatura ativa.

Caso a assinatura esteja inativa:

```json
{
    "message": "Assinatura inativa. Renove seu plano para continuar."
}
```

---

# Estrutura da API

```text
Frontend
    ↓
Laravel Sanctum
    ↓
Middleware Plan Active
    ↓
Controllers
    ↓
Models
    ↓
Database
```

---

# Licença

Projeto desenvolvido para gerenciamento de serviços de impressão 3D.
