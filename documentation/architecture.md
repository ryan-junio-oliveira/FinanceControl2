# Arquitetura

## Camadas

```
┌─────────────────────────────────────────────┐
│  Presentation (Controllers + Views)         │
│  Web: pages.*  |  API: Api/V1/*             │
├─────────────────────────────────────────────┤
│  HTTP (FormRequests + Resources)            │
│  Validação, autorização, transformação      │
├─────────────────────────────────────────────┤
│  Services (app/Services/*)                  │
│  Regras de domínio, transações, queries     │
├─────────────────────────────────────────────┤
│  Models (Eloquent)                          │
│  Entidades, relações, casts, observers      │
├─────────────────────────────────────────────┤
│  Support (app/Support/*)                    │
│  Helpers: Fin, Audit, Dashboard, Catalogs   │
└─────────────────────────────────────────────┘
```

## Princípios

- **Controllers finos**: apenas HTTP (request → service → response)
- **Services**: regras de domínio reutilizáveis (web + API)
- **FormRequests**: validação e mensagens pt-BR centralizadas
- **Resources**: transformação de models para JSON (API)
- **Observers**: auditoria automática (AuditLog)
- **Middleware**: `family.role` (papéis) + `family.ownership` (posse do recurso)

## SOLID

| Princípio | Aplicação |
|-----------|-----------|
| **S**ingle Responsibility | Controllers HTTP, Services domínio, Resources transformação |
| **O**pen/Closed | Novos endpoints = novos métodos nos Services existentes |
| **L**iskov | Services são intercambiáveis entre web e API |
| **I**nterface Segregation | Cada Service tem métodos específicos |
| **D**ependency Inversion | Controllers dependem de Services (injeção) |

## Fluxo de requisição

```
Request → Middleware (auth, family) → Controller → Service → Model → Response
```

## Autenticação

- **Web**: sessão (guard `web`)
- **API**: token Sanctum (Bearer)

## Auditoria

Toda criação/edição/exclusão de entidade relevante gera `AuditLog` via observers, capturando: usuário, ação, IP, user-agent, método, URL e mudanças (diff).
