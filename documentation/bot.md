# Bot (Telegram / WhatsApp)

Assistente conversacional do FinFamília: o usuário registra e consulta tudo pelo celular, espelhando o frontend em mensagens.

## Canais

| Canal | Custo (2026) | Status |
|-------|--------------|--------|
| **Telegram** (Bot API) | Grátis e ilimitado | ✅ Implementado |
| **WhatsApp** (Cloud API) | Respostas em janela de 24h: grátis e ilimitado; templates iniciados pelo bot: pagos por mensagem | 🔌 Preparado (falta o driver) |

Detalhe do WhatsApp: desde 11/2024 as conversas de serviço (usuário chama → bot responde em 24h) são **gratuitas e ilimitadas** — não há mais o teto de 1.000. Só templates de marketing/utilidade/autenticação iniciados pelo bot são cobrados (jul/2025 em diante, por mensagem entregue). Ou seja: para o nosso fluxo (usuário sempre chama primeiro), o custo tende a zero.

## Arquitetura (driver trocável)

```
Webhook → BotDriver::parseWebhook → IncomingMessage → BotRouter → Handler → app/Services/*
```

- `app/Bot/Contracts/BotDriver.php` — contrato do canal (Strategy)
- `app/Bot/Drivers/TelegramDriver.php` — Bot API via HTTP
- `app/Bot/Drivers/NullDriver.php` — testes/dev (só registra)
- `app/Bot/BotManager.php` — factory (`BOT_DRIVER`)
- `app/Bot/BotRouter.php` — menu + retomada de fluxos
- `app/Bot/Handlers/*` — telas do app viradas em conversa
- `app/Bot/ConversationState.php` — estado em Cache (TTL `BOT_STATE_TTL`)
- `app/Bot/BotPresenter.php` — formatação (moeda, datas)

Trocar de canal = implementar `BotDriver` + `BOT_DRIVER=whatsapp`. As conversas não mudam.

> 📖 Passo a passo completo de configuração: [bot-telegram-setup.md](bot-telegram-setup.md).

## Menu (espelha a sidebar)

1. **Dados financeiros** — saldo, resultado do mês, a pagar, patrimônio
2. **Despesas** — consultar mês + lançar (fluxo com confirmação)
3. **Receitas** — consultar mês + lançar
4. **Cartões** — faturas em aberto + lançar compra
5. **Contas** — saldos por conta
6. **Investimentos** — patrimônio e metas (leitura)

## Comprovantes (foto/PDF)

Envie a **foto do comprovante** (ou o **PDF**) no chat e o bot lê com OCR local (Tesseract, grátis/offline), identifica **banco, valor, data, canal (Pix/TED/boleto/cartão) e direção (despesa/receita)** — e **pergunta o que não entendeu** (valor? data? tipo? conta? categoria?) antes de confirmar. O arquivo vira **anexo do lançamento**. PDFs com texto embarcado são lidos direto; PDF escaneado pede foto.

## Alertas proativos

Todo dia às 08:00 o `notify:vencimentos` (mesmo agendamento das notificações) também envia **uma mensagem agregada por usuário** no Telegram para quem tem o chat vinculado — contas e faturas vencendo em até 3 dias. Respeita as mesmas preferências da conta (`fatura_vencimento`, `conta_vencimento`).

## Vínculo da conta

Cada usuário tem um `bot_code` de 6 dígitos (perfil → “Bot no Celular”). No Telegram: `/start 123456`. O vínculo fica em `bot_identities` (canal + chat → usuário).

## Setup do Telegram

```sh
# .env
BOT_DRIVER=telegram
BOT_TELEGRAM_TOKEN=123456:ABC...
BOT_TELEGRAM_SECRET=um-segredo-qualquer

php artisan bot:telegram-webhook https://sua-url.com/api/bot/telegram
```

## Testes

`tests/Feature/BotTest.php` usa o `NullDriver` (sem rede): vínculo, menu, consulta, fluxo de lançamento com confirmação e cancelamento.
