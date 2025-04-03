# API de Gerenciamento de Servidores

API para gerenciamento de servidores públicos (efetivos e temporários), unidades e lotações.

## Requisitos

- Docker
- Docker Compose

## Instalação

1. Clone o repositório:

```bash
git clone https://github.com/seu-usuario/api-servidores.git
cd api-servidores
```

2. Inicie os containers Docker:

```bash
docker-compose up -d
```

3. Execute o script de configuração:

```bash
docker-compose exec app ./setup.sh
```

## Endpoints da API

### Autenticação

- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Renovar token
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Informações do usuário autenticado

### Servidores Efetivos

- `GET /api/servidores-efetivos` - Listar servidores efetivos
- `POST /api/servidores-efetivos` - Criar servidor efetivo
- `GET /api/servidores-efetivos/{id}` - Obter servidor efetivo
- `PUT /api/servidores-efetivos/{id}` - Atualizar servidor efetivo
- `DELETE /api/servidores-efetivos/{id}` - Excluir servidor efetivo
- `GET /api/servidores-efetivos/unidade/{unid_id}` - Listar servidores por unidade
- `GET /api/servidores-efetivos/endereco-funcional/{nome}` - Buscar endereço funcional

### Servidores Temporários

- `GET /api/servidores-temporarios` - Listar servidores temporários
- `POST /api/servidores-temporarios` - Criar servidor temporário
- `GET /api/servidores-temporarios/{id}` - Obter servidor temporário
- `PUT /api/servidores-temporarios/{id}` - Atualizar servidor temporário
- `DELETE /api/servidores-temporarios/{id}` - Excluir servidor temporário

### Unidades

- `GET /api/unidades` - Listar unidades
- `POST /api/unidades` - Criar unidade
- `GET /api/unidades/{id}` - Obter unidade
- `PUT /api/unidades/{id}` - Atualizar unidade
- `DELETE /api/unidades/{id}` - Excluir unidade

### Lotações

- `GET /api/lotacoes` - Listar lotações
- `POST /api/lotacoes` - Criar lotação
- `GET /api/lotacoes/{id}` - Obter lotação
- `PUT /api/lotacoes/{id}` - Atualizar lotação
- `DELETE /api/lotacoes/{id}` - Excluir lotação

### Fotos

- `POST /api/pessoas/{pes_id}/fotos` - Enviar foto
- `GET /api/fotos/{hash}` - Obter URL temporária da foto
- `DELETE /api/fotos/{id}` - Excluir foto

### Cidades

- `GET /api/cidades` - Listar cidades
- `GET /api/cidades/{id}` - Obter cidade

## Usuário Padrão

- Email: admin@example.com
- Senha: password

## Licença

Este projeto está licenciado sob a licença MIT.
```

## Comandos para Iniciar o Projeto

Para iniciar o projeto, execute os seguintes comandos:

```bash
docker-compose up -d
```

```bash
docker-compose exec app composer install
```

```bash
docker-compose exec app php artisan key:generate
```

```bash
docker-compose exec app php artisan jwt:secret
```

```bash
docker-compose exec app php artisan migrate:fresh --seed
