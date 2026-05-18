<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/services/AuthService.php';

redirecionar_se_logado();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    try {
        $usuario = AuthService::autenticar($email, $senha);

        if ($usuario) {
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            header('Location: ' . app_url('itens/listar.php'));
            exit;
        }

        $erro = 'E-mail ou senha invalidos.';
    } catch (Throwable $erroAplicacao) {
        $erro = $erroAplicacao->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <section class="auth-brand">
            <h1>ReUse</h1>
            <p>Entre para cadastrar itens, consultar doacoes e acompanhar seus pontos.</p>
        </section>

        <form method="post" class="form-card">
            <h2>Entrar</h2>

            <?php if ($erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endif; ?>

            <label>
                E-mail
                <input type="email" name="email" required>
            </label>

            <label>
                Senha
                <input type="password" name="senha" required>
            </label>

            <button type="submit" class="btn primary">Entrar</button>

            <p class="links">
                <a href="cadastro.php">Criar cadastro</a>
            </p>
        </form>
    </main>
</body>
</html>
