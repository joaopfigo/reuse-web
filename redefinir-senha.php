<?php
require_once __DIR__ . '/app/helpers/auth.php';

redirecionar_se_logado();

$token = trim((string) ($_GET['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$erros = [];
$sucesso = false;
$tokenRow = null;

if ($token) {
    $stmt = db()->prepare(
        'SELECT t.*, u.id AS usuario_id FROM tokens_senha t
         JOIN usuarios u ON u.id = t.usuario_id
         WHERE t.token = :token AND t.usado = 0 AND t.expira_em > NOW()'
    );
    $stmt->execute([':token' => $tokenHash]);
    $tokenRow = $stmt->fetch();
}

if (!$tokenRow) {
    $erros[] = 'Link inválido ou expirado. Solicite um novo em "Esqueci minha senha".';
}

if (!$erros && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $senha = $_POST['senha'] ?? '';
    $senhaConfirm = $_POST['senha_confirm'] ?? '';

    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif ($senha !== $senhaConfirm) {
        $erros[] = 'As senhas não coincidem.';
    } else {
        db()->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id')
            ->execute([':hash' => password_hash($senha, PASSWORD_DEFAULT), ':id' => $tokenRow['usuario_id']]);
        db()->prepare('UPDATE tokens_senha SET usado = 1 WHERE token = :token')
            ->execute([':token' => $tokenHash]);
        $sucesso = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Redefinir senha</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
    <?= pwa_head_tags() ?>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <span class="hero-tag">Nova senha</span>
            <h1>Defina uma senha segura para voltar ao ReUse.</h1>
            <p>O link é temporário e será invalidado depois da redefinição.</p>
        </section>

        <section class="auth-panel">
            <?php if ($sucesso): ?>
                <div class="section-header">
                    <h2>Senha redefinida</h2>
                    <p>Agora você já pode entrar com a nova senha.</p>
                </div>
                <div class="alert success">Senha redefinida com sucesso.</div>
                <a class="btn primary" href="login.php">Fazer login</a>
            <?php else: ?>
                <div class="section-header">
                    <h2>Redefinir senha</h2>
                    <p>Use pelo menos 8 caracteres. Evite senhas repetidas de outros serviços.</p>
                </div>

                <?php foreach ($erros as $erro): ?>
                    <div class="alert error"><?= e($erro) ?></div>
                <?php endforeach; ?>

                <?php if (!$erros): ?>
                <form method="post" class="section-stack">
                    <?= csrf_input() ?>
                    <label>
                        Nova senha (mínimo 8 caracteres)
                        <input type="password" name="senha" required minlength="8">
                    </label>
                    <label>
                        Confirmar nova senha
                        <input type="password" name="senha_confirm" required minlength="8">
                    </label>
                    <button type="submit" class="btn primary">Salvar senha</button>
                </form>
                <?php else: ?>
                    <div class="auth-links"><a href="esqueci-senha.php">Solicitar novo link</a></div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
