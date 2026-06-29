<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/repositories/EmailConfirmacaoRepo.php';
require_once __DIR__ . '/app/repositories/UsuarioRepo.php';

$mensagem = '';
$tipo = 'error';
$token = (string) ($_GET['token'] ?? '');

if ($token === '') {
    $mensagem = 'Link de confirmacao invalido.';
} else {
    $registro = EmailConfirmacaoRepo::buscarValido($token);

    if (!$registro) {
        $mensagem = 'Este link de confirmacao expirou ou ja foi utilizado.';
    } else {
        UsuarioRepo::confirmarEmail((int) $registro['usuario_id']);
        EmailConfirmacaoRepo::marcarUsado((int) $registro['id']);
        $tipo = 'success';
        $mensagem = 'E-mail confirmado com sucesso. Agora sua conta pode publicar itens e fazer reservas.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Confirmar e-mail</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
    <?= pwa_head_tags() ?>
</head>
<body class="auth-page">
    <main class="auth-shell compact-auth">
        <section class="auth-panel">
            <div class="section-header">
                <h1>Confirmacao de e-mail</h1>
                <p>A verificacao libera as principais acoes da sua conta no ReUse.</p>
            </div>

            <div class="alert <?= e($tipo) ?>"><?= e($mensagem) ?></div>

            <div class="auth-links">
                <a href="login.php">Entrar na conta</a>
            </div>
        </section>
    </main>
</body>
</html>
