# PWA — Prumo instalável

O app web é um **PWA instalável**: ícone na tela inicial, tela cheia e leitura offline do que já foi visitado.

## O que foi adicionado

| Arquivo | Papel |
|---------|-------|
| `public/manifest.webmanifest` | Nome, cores, ícones, atalhos (Dashboard, Nova despesa/receita) |
| `public/icons/` | Ícones 192/512 (maskable) + apple-touch 180, gerados da identidade |
| `public/sw.js` | Service worker: navegação network-first com fallback offline; estáticos em cache |
| `resources/views/pages/offline.blade.php` | Página exibida sem rede (rota `/offline`, pública) |
| `resources/js/app.js` | Registro do SW + banner “Instalar” (com dispensa persistida) |

## Estratégia de cache (segura p/ CRUD autenticado)

- **Navegações**: tenta a rede → usa o cache da página → cai no `/offline`. Nunca serve dados velhos como se fossem novos sem tentar a rede antes.
- **Estáticos** (css/js/fontes/img do próprio domínio): do cache, atualizando em fundo.
- **POST e outros domínios**: nunca interceptados.

## Testando local

1. Sirva com HTTPS (túnel) ou `http://localhost` (única exceção HTTP que o navegador aceita p/ SW).
2. DevTools → Application → Manifest/Service Workers: confira registro e cache `finfamilia-v1`.
3. DevTools → Network → Offline: navegue — páginas visitadas abrem do cache; novas caem no `/offline`.
4. No celular: “Adicionar à tela inicial” (o banner aparece automaticamente quando o navegador permite).

## Ao alterar o `sw.js`

Suba a constante `CACHE` (`finfamilia-v1` → `v2`): o SW novo assume e limpa o cache antigo sozinho.

## Limites honestos

- iOS: instalação e push só funcionam bem a partir do iOS 16.4+, e o iOS é mais restrito com cache em segundo plano.
- Offline é **leitura** do que já foi visitado — lançar despesa exige rede (o formulário nem envia sem conexão).
- Se um dia precisar de loja, push confiável no iOS ou sensores/offline-first real, o caminho é o app Flutter consumindo a API v1 (já pronta e documentada).
