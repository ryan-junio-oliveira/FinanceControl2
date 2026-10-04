# API v1 — Prumo

REST API para o bot (smartphone). Autenticação via **Sanctum** (Bearer token).

## Swagger UI

A documentação interativa (Swagger UI) está disponível em:

```
GET /api/documentation
```

O JSON OpenAPI é gerado automaticamente das anotações nos controllers (`app/Http/Controllers/Api/V1`).

## Autenticação

1. `POST /api/v1/auth/token` com `email`, `password`, `device_name` → retorna `token`
2. Envie `Authorization: Bearer <token>` nas demais requisições
3. `DELETE /api/v1/auth/token` revoga o token atual

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/v1/auth/token` | Emite token |
| DELETE | `/api/v1/auth/token` | Revoga token |
| GET | `/api/v1/user` | Usuário autenticado |
| GET | `/api/v1/transactions/{type}` | Lista lançamentos |
| POST | `/api/v1/transactions/{type}` | Cria lançamento |
| GET | `/api/v1/transactions/{type}/{id}` | Detalha lançamento |
| PATCH | `/api/v1/transactions/{type}/{id}` | Atualiza lançamento |
| DELETE | `/api/v1/transactions/{type}/{id}` | Exclui lançamento |
| POST | `/api/v1/transactions/{type}/{id}/settle` | Marca como pago |
| GET | `/api/v1/accounts` | Lista contas |
| POST | `/api/v1/accounts` | Cria conta |
| GET | `/api/v1/accounts/{id}` | Detalha conta |
| PATCH | `/api/v1/accounts/{id}` | Atualiza conta |
| DELETE | `/api/v1/accounts/{id}` | Exclui conta |
| POST | `/api/v1/accounts/transfer` | Transferência interna |
| GET | `/api/v1/cards` | Lista cartões |
| POST | `/api/v1/cards` | Cria cartão |
| GET | `/api/v1/cards/{id}` | Detalha cartão |
| PATCH | `/api/v1/cards/{id}` | Atualiza cartão |
| DELETE | `/api/v1/cards/{id}` | Exclui cartão |
| POST | `/api/v1/cards/items` | Lança compra/estorno |
| POST | `/api/v1/cards/items/{id}/settle` | Liquida item |
| POST | `/api/v1/cards/{id}/pay-invoice` | Paga fatura |
| GET | `/api/v1/categories` | Lista categorias |
| POST | `/api/v1/categories` | Cria categoria |
| GET | `/api/v1/categories/{id}` | Detalha categoria |
| PATCH | `/api/v1/categories/{id}` | Atualiza categoria |
| DELETE | `/api/v1/categories/{id}` | Exclui categoria |
| GET | `/api/v1/assets` | Lista ativos |
| POST | `/api/v1/assets` | Cria ativo |
| PATCH | `/api/v1/assets/{id}` | Atualiza ativo |
| DELETE | `/api/v1/assets/{id}` | Exclui ativo |
| POST | `/api/v1/contributions` | Registra aporte/rendimento |
| GET | `/api/v1/group/members` | Lista membros |
| GET | `/api/v1/group/invites` | Lista convites |
| POST | `/api/v1/group/invites` | Convida membro |
| DELETE | `/api/v1/group/invites/{id}` | Revoga convite |
| DELETE | `/api/v1/group/members/{id}` | Remove membro |
| PATCH | `/api/v1/group/members/{id}/role` | Altera papel |
| GET | `/api/v1/dashboard` | KPIs e gráficos |
| GET | `/api/v1/admin/logs` | Logs de auditoria (admin) |

## Middleware

| Middleware | Ação |
|------------|------|
| `auth:sanctum` | Exige token válido |
| `group.ownership:{param}` | Valida que o recurso pertence ao grupo |

## Respostas

- Sucesso: `200`/`201` com JSON
- Erro de validação: `422` com mensagens pt-BR
- Não autorizado: `401`/`403`
- Não encontrado: `404`
