<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/services/AuthService.php';

redirecionar_se_logado();

$erro = '';
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $senha = $_POST['senha'] ?? '';

    try {
        $usuario = AuthService::autenticar($email, $senha);

        if ($usuario) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            header('Location: ' . app_url('itens/listar.php'));
            exit;
        }

        $erro = 'E-mail ou senha inválidos.';
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
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <span class="hero-tag">Rede de reuso</span>
            <h1>Organize doações com clareza, confiança e rastreabilidade.</h1>
            <p>Entre na sua conta para acompanhar itens publicados, reservas em andamento, pontos e interações da comunidade em um só lugar.</p>

            <div class="auth-feature-list">
                <div class="auth-feature">
                    <strong>Fluxo completo</strong>
                    <span>Do anúncio à confirmação da entrega com atualização automática de pontos.</span>
                </div>
                <div class="auth-feature">
                    <strong>Mais organização</strong>
                    <span>Feed, reservas, notificações e histórico reunidos em uma experiência mais clara.</span>
                </div>
            </div>
        </section>

        <form method="post" class="auth-panel">
            <div class="section-header">
                <h2>Entrar</h2>
                <p>Acesse sua conta para continuar cuidando das suas doações e reservas.</p>
            </div>

            <?= csrf_input() ?>

            <?php if ($erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endif; ?>

            <label>
                E-mail
                <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
            </label>

            <label>
                Senha
                <input type="password" name="senha" autocomplete="current-password" required>
            </label>

            <button type="submit" class="btn primary">Entrar</button>

            <div class="auth-links">
                <a href="cadastro.php">Criar cadastro</a>
                <a href="esqueci-senha.php">Esqueci minha senha</a>
            </div>
        </form>
    </main>
</body>
</html>
