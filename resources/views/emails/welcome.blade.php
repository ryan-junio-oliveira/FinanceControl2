<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bem-vindo ao FinFamília</title>
    <style>
        body { margin: 0; padding: 0; background: #F1F5F9; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; color: #111827; }
        .wrap { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .head { background: #064E3B; color: #fff; padding: 28px 32px; }
        .head h1 { margin: 0 0 4px; font-size: 22px; }
        .head p { margin: 0; color: #A7F3D0; font-size: 13px; }
        .body { padding: 28px 32px; }
        .body p { font-size: 14px; line-height: 1.6; color: #374151; margin: 0 0 16px; }
        .btn { display: block; text-align: center; background: #047857; color: #fff; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 20px; border-radius: 8px; margin: 20px 0; }
        .btn:hover { background: #065F46; }
        .meta { font-size: 12px; color: #9ca3af; line-height: 1.6; }
        .foot { padding: 18px 32px; background: #F8FAFC; border-top: 1px solid #e2e8f0; font-size: 12px; color: #9ca3af; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <h1>FinFamília</h1>
                <p>Você foi adicionado à conta {{ $accountName }}</p>
            </div>
            <div class="body">
                <p>Olá, <strong>{{ $invitation->name }}</strong>!</p>
                <p>O administrador da conta <strong>{{ $accountName }}</strong> adicionou você ao FinFamília como <strong>{{ \App\Models\User::ROLES[$invitation->role] ?? $invitation->role }}</strong>.</p>
                <p>Para começar, clique no botão abaixo e defina sua senha. O link é de uso único e expira após o primeiro acesso.</p>
                <a class="btn" href="{{ $link }}">Definir minha senha e entrar</a>
                <p class="meta">Se o botão não funcionar, copie e cole este link no navegador:<br>{{ $link }}</p>
                <p class="meta">Não solicitou este convite? Ignore este e-mail. Seus dados estão seguros.</p>
            </div>
            <div class="foot">
                FinFamília · Gestão financeira pessoal e em grupo<br>
                Seus dados pertencem a você — nunca solicitamos sua senha bancária por e-mail.
            </div>
        </div>
    </div>
</body>
</html>