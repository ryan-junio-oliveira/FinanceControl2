# Domínio

## Entidades

| Entidade | Descrição |
|----------|-----------|
| `Group` | Conta do grupo (tenancy por `group_id`) |
| `GroupSetting` | Preferências do grupo (notificações, frase secreta) |
| `User` | Membros (admin, co_admin, dependente, junior) |
| `Category` | Categorias de despesa/receita |
| `Account` | Contas bancárias (ligadas a um `Bank`) |
| `Bank` | Catálogo de bancos (código, nome, cor) |
| `Transaction` | Lançamentos (despesa, receita, transferência, aporte) |
| `CreditCard` | Cartões de crédito |
| `CardTransaction` | Itens de fatura (compras/estornos) |
| `Portfolio` | Carteiras de investimento |
| `Asset` | Ativos (renda fixa, FII, ação, ETF, previdência) |
| `Contribution` | Aportes e rendimentos |
| `Invitation` | Convites pendentes |
| `Attachment` | Anexos/comprovantes (OCR via `ocr_status`: queued→done/failed) |
| `AuditLog` | Trilha de auditoria |

## Relações

```
Group 1───* User
Group 1───* Category
Group 1───* Account *───1 Bank
Group 1───* Transaction *───1 User (membro)
Transaction *───1 Account
Transaction *───1 Category
Group 1───* CreditCard *───1 Account
CreditCard 1───* CardTransaction *───1 User
Group 1───* Portfolio 1───* Asset
Portfolio 1───* Contribution *───1 Asset
Group 1───* Invitation
Group 1───* AuditLog *───1 User
```

## Regras de negócio

### Lançamentos
- **Parcelamento**: divide em centavos (sem drift), vence 1x ao mês
- **Status**: pago, pendente, agendado
- **Fixos**: repetem todo mês (is_fixed)

### Cartões
- **Fatura**: período entre fechamento e vencimento
- **Liquidação**: item pago gera despesa na conta vinculada
- **Pagamento de fatura**: liquida todos os pendentes

### Investimentos
- **Aporte**: sai da conta (tipo `aporte`), incrementa ativo
- **Rendimento**: incrementa ativo sem sair da conta
- **Carteira "Geral"**: criada automaticamente se não informada

### Auditoria
- Observers gravam `created`/`updated`/`deleted` automaticamente
- Login/logout via eventos do Laravel
- Ignora CLI/seed (sem usuário autenticado)
