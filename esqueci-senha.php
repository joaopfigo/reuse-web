<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/repositories/UsuarioRepo.php';

redirecionar_se_logado();

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $usuario = $email ? UsuarioRepo::buscarPorEmail($email) : null;

    if ($usuario) {
        $token  = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Invalidar tokens anteriores deste usuário
        db()->prepare('UPDATE tokens_senha SET usado = 1 WHERE usuario_id = :id AND usado = 0')
            ->execute([':id' => $usuario['id']]);

        db()->prepare(
            'INSERT INTO tokens_senha (usuario_id, token, expira_em) VALUES (:id, :token, :expira)'
        )->execute([':id' => $usuario['id'], ':token' => $token, ':expira' => $expira]);

        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $protocolo . '://' . $_SERVER['HTTP_HOST'] . app_url('redefinir-senha.php') . '?token=' . $token;

        // Tentar enviar e-mail (requer servidor SMTP configurado)
        @mail(
            $usuario['email'],
            'ReUse — Redefinição de senha',
            "Clique no link para redefinir sua senha:\n\n{$link}\n\nO link expira em 1 hora.",
            'From: noreply@reuse.local'
        );
    }

    // Mensagem genérica: não revela se o e-mail existe
    $mensagem = 'Se o e-mail estiver cadastrado, um link de redefinição foi enviado.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Esqueci minha senha</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <section class="auth-brand">
            <h1>ReUse</h1>
            <p>Informe seu e-mail para receber o link de redefinição.</p>
        </section>

        <?php if ($mensagem): ?>
            <div class="alert success"><?= e($mensagem) ?></div>
        <?php endif; ?>

        <form method="post" class="form-card">
            <label>
                E-mail
                <input type="email" name="email" required>
            </label>
            <button type="submit" class="btn primary">Enviar link</button>
            <p class="links"><a href="login.php">Voltar ao login</a></p>
        </form>
    </main>
</body>
</html>
