# Configurando o Bot do Telegram

Guia passo a passo para ligar o assistente do Prumo no Telegram, do zero ao primeiro `/start`.

## 1. Pré-requisitos

- App Laravel rodando (`php artisan serve`)
- Conta no Telegram (a sua, pessoal)
- Migration do bot aplicada (`bot_identities` + `users.bot_code`)

## 2. Criar o bot no BotFather

1. No Telegram, busque `@BotFather` (oficial, com selo ✓) e toque **START**
2. Envie `/newbot`
3. Escolha um **nome** (ex.: `Prumo`)
4. Escolha um **username** terminado em `bot` (ex.: `finfamiliaapp_bot`; se estiver em uso, varie: `finfamilia_br_bot`)
5. Guarde o **token** (`123456:ABC...`)

> ⚠️ Quem tem o token controla o bot. Não commite o token nem cole em canais públicos. Se vazar, gere outro com `/revoke` no BotFather.

Opcionais no BotFather: `/setdescription`, `/setuserpic`, `/setcommands` (sugestão: `start`, `cancelar`).

## 3. Configurar o `.env`

```ini
BOT_DRIVER=telegram
BOT_TELEGRAM_TOKEN=seu-token-aqui
BOT_TELEGRAM_SECRET=um-segredo-qualquer
BOT_STATE_TTL=30
```

## 4. Expor o app com HTTPS público

O Telegram só entrega updates em URL **HTTPS pública** — `localhost` não serve.

| Ambiente | Como |
|----------|------|
| Teste local | `cloudflared tunnel --url http://localhost:8000` (sem conta) ou `ngrok http 8000` (exige conta + authtoken) |
| Produção | Domínio próprio com HTTPS |

Anote a URL pública (ex.: `https://xxx.trycloudflare.com`). URLs de túnel gratuito **mudam a cada reinício** — nesse caso, refaça o passo 5.

> 💡 **Script automático**: rode `start-bot.bat` (Windows, duplo clique) ou `bash start-bot.sh`
> (Git Bash). Ele sobe o servidor se necessário, inicia o túnel, extrai a URL nova
> e registra o webhook sozinho. Use sempre depois de reiniciar o notebook.

## 5. Registrar o webhook

```sh
php artisan bot:telegram-webhook https://SUA-URL/api/bot/telegram
```

Saída esperada: `Webhook registrado: ...`. Para conferir o que o Telegram tem registrado:

```sh
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

Troubleshooting: `last_error_message` e `pending_update_count` altos indicam que sua URL não está acessível.

## 6. Vincular sua conta

1. No sistema web: **Perfil → Bot no Celular → Gerar código** (6 dígitos)
2. No Telegram, abra **o seu bot** (não o BotFather!) e envie `/start SEU-CODIGO`
3. O menu aparece — pronto 🎉

## 7. Testando os fluxos

- `1` — Dados financeiros do mês
- `2` → Lançar — fluxo completo com confirmação (cria o lançamento de verdade)
- `cancelar` — volta ao menu a qualquer momento

## 8. Problemas comuns

| Sintoma | Causa provável | Solução |
|---------|----------------|---------|
| Bot não responde nada | Webhook aponta para URL morta (túnel reiniciado) | Refaça o passo 5 com a URL atual |
| `Invalid bot passed` | Você enviou `/start CODIGO` para o **BotFather** | Envie no chat do **seu bot** |
| `❌ Código inválido` | Código errado ou regenerado | Gere de novo no perfil |
| `{"ok":true}` mas sem resposta | Falha ao enviar (token/secret) | Confira `storage/logs/laravel.log` (`[bot] Falha ao enviar...`) |
| Bot responde no teste, mas não no celular | Outro chat = outro vínculo | Cada chat precisa do próprio `/start CODIGO` |

## 9. Indo para produção

- Use domínio fixo com HTTPS e `BOT_TELEGRAM_SECRET` forte
- Regenere o token se ele circulou em chats (`/revoke` no BotFather)
- Monitore `storage/logs/laravel.log` (o driver nunca dá 500 no webhook: erros viram log)
