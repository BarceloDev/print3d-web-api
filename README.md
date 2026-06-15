# 🖨️ Print3D Web API

API REST desenvolvida em Laravel para gerenciamento de clientes, pedidos e acompanhamento do fluxo de produção de impressões 3D.

---

## ✨ Funcionalidades

### 🔐 Autenticação

- Registro de usuários
- Login
- Logout
- Recuperação do usuário autenticado

### 👥 Clientes

- Cadastro
- Atualização
- Remoção
- Listagem

### 📦 Pedidos

- Cadastro de pedidos
- Atualização
- Exclusão
- Alteração de status
- Upload de arquivos
- Aprovação pelo cliente
- Rejeição pelo cliente

### 📊 Dashboard

- Métricas operacionais
- Indicadores
- Gráficos

---

## 🛠️ Tecnologias Utilizadas

### Backend

- PHP 8.2+
- Laravel 12
- Laravel Sanctum

### Banco de Dados

- PostgreSQL

### Armazenamento

- Laravel Storage

---

## 📂 Estrutura do Projeto

```text
app/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
└── Providers/

database/
├── migrations/
├── factories/
└── seeders/

routes/
└── api.php
```

---

## 🔐 Autenticação

A autenticação é realizada utilizando Laravel Sanctum.

Exemplo de header:

```http
Authorization: Bearer TOKEN
```

---

## 📡 Principais Endpoints

### Autenticação

```http
POST /api/register
POST /api/login
POST /api/logout
GET /api/me
```

### Clientes

```http
GET    /api/clients
POST   /api/clients
PUT    /api/clients/{id}
DELETE /api/clients/{id}
```

### Pedidos

```http
GET    /api/orders
POST   /api/orders
PUT    /api/orders/{id}
DELETE /api/orders/{id}
PATCH  /api/orders/{id}/status
```

### Dashboard

```http
GET /api/dashboard/charts
```

### Aprovação Pública

```http
GET   /api/public/orders/{token}
PATCH /api/public/orders/{token}/approve
PATCH /api/public/orders/{token}/reject
```

---

## ⚙️ Configuração

Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

Configure o PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=print3d_web_api
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

---

## 🚀 Instalação

Clone o repositório:

```bash
git clone https://github.com/BarceloDev/print3d-web-api.git
```

Entre na pasta:

```bash
cd print3d-web-api
```

Instale as dependências:

```bash
composer install
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

Execute as migrations:

```bash
php artisan migrate
```

Crie o link simbólico para uploads:

```bash
php artisan storage:link
```

Inicie o servidor:

```bash
php artisan serve
```

Servidor local:

```text
http://localhost:8000
```

---

## 🐳 Docker

O projeto possui suporte a Docker através do arquivo:

```text
Dockerfile
```

---

## 🔗 Frontend

Frontend oficial:

https://github.com/BarceloDev/print3d

---

## 👨‍💻 Autor

**Guilherme Barcelo**

- LinkedIn: https://www.linkedin.com/in/guilherme-barcelo
- Instagram: https://www.instagram.com/guibarcelo_

---

API desenvolvida para gerenciamento de operações de impressão 3D.
