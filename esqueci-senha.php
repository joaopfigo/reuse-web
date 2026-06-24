<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/repositories/UsuarioRepo.php';

redirecionar_se_logado();

$mensagem = '';
$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $usuario = $email ? UsuarioRepo::buscarPorEmail($email) : null;

    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        db()->prepare('UPDATE tokens_senha SET usado = 1 WHERE usuario_id = :id AND usado = 0')
            ->execute([':id' => $usuario['id']]);

        db()->prepare(
            'INSERT INTO tokens_senha (usuario_id, token, expira_em) VALUES (:id, :token, :expira)'
        )->execute([':id' => $usuario['id'], ':token' => $tokenHash, ':expira' => $expira]);

        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $protocolo . '://' . $_SERVER['HTTP_HOST'] . app_url('redefinir-senha.php') . '?token=' . $token;
        $host = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'reuse.local'));
        $dominioEmail = preg_replace('/^www\./', '', $host);
        $remetente = filter_var('no-reply@' . $dominioEmail, FILTER_VALIDATE_EMAIL)
            ? 'no-reply@' . $dominioEmail
            : 'noreply@reuse.local';

        @mail(
            $usuario['email'],
            'ReUse - Redefinição de senha',
            "Clique no link para redefinir sua senha:\n\n{$link}\n\nO link expira em 1 hora.",
            'From: ' . $remetente
        );
    }

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
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <span class="hero-tag">Acesso seguro</span>
            <h1>Recupere sua conta sem expor seus dados.</h1>
            <p>Enviaremos um link temporário para o e-mail cadastrado, sem revelar se a conta existe ou não.</p>
        </section>

        <form method="post" class="auth-panel">
            <div class="section-header">
                <h2>Esqueci minha senha</h2>
                <p>Digite o e-mail usado no cadastro para receber as instruções de redefinição.</p>
            </div>
            <?= csrf_input() ?>
            <?php if ($mensagem): ?>
                <div class="alert success"><?= e($mensagem) ?></div>
            <?php endif; ?>
            <label>
                E-mail
                <input type="email" name="email" value="<?= e($email) ?>" required>
            </label>
            <button type="submit" class="btn primary">Enviar link</button>
            <div class="auth-links"><a href="login.php">Voltar ao login</a></div>
        </form>
    </main>
</body>
</html>
