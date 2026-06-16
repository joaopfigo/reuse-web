<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/validar.php';
require_once __DIR__ . '/app/services/AuthService.php';

redirecionar_se_logado();

$erro = '';
$sucesso = '';
$nome = trim((string) ($_POST['nome'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$telefone = trim((string) ($_POST['telefone'] ?? ''));
$bairro = trim((string) ($_POST['bairro'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $senha = $_POST['senha'] ?? '';
    $telefoneRepo = $telefone !== '' ? $telefone : null;

    if (!$nome || !validar_email($email) || strlen($senha) < 8 || !$bairro) {
        $erro = 'Preencha nome, e-mail válido, senha com 8 caracteres e bairro.';
    } else {
        try {
            AuthService::cadastrar($nome, $email, $senha, $telefoneRepo, $bairro);
            $sucesso = 'Cadastro criado. Agora você pode fazer login.';
            $nome = '';
            $email = '';
            $telefone = '';
            $bairro = '';
        } catch (PDOException $e) {
            $erro = ((string) $e->getCode() === '23000')
                ? 'Este e-mail já está cadastrado.'
                : 'Não foi possível concluir o cadastro agora.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Cadastro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/experience.css?v=20260615">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <span class="hero-tag">Entrada na comunidade</span>
            <h1>Crie sua conta e participe de uma rede de reuso mais organizada.</h1>
            <p>Cadastre-se para publicar itens, reservar doações, acompanhar notificações e movimentar sua carteira de pontos com segurança.</p>

            <div class="auth-feature-list">
                <div class="auth-feature">
                    <strong>Cadastro simples</strong>
                    <span>Informações objetivas para você começar rápido e manter apenas dados úteis para a retirada.</span>
                </div>
                <div class="auth-feature">
                    <strong>Comunidade confiável</strong>
                    <span>Regras de confirmação, avaliações e histórico ajudam a tornar as trocas mais seguras.</span>
                </div>
            </div>
        </section>

        <form method="post" class="auth-panel">
            <div class="section-header">
                <h2>Criar cadastro</h2>
                <p>Abra sua conta para começar a doar, reservar e acompanhar toda a jornada no ReUse.</p>
            </div>

            <?= csrf_input() ?>

            <?php if ($erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alert success"><?= e($sucesso) ?></div>
            <?php endif; ?>

            <label>
                Nome
                <input type="text" name="nome" value="<?= e($nome) ?>" autocomplete="name" required>
            </label>

            <label>
                E-mail
                <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
            </label>

            <div class="grid-2">
                <label>
                    Senha
                    <input type="password" name="senha" minlength="8" autocomplete="new-password" required>
                </label>

                <label>
                    Bairro
                    <input type="text" name="bairro" value="<?= e($bairro) ?>" autocomplete="address-level2" required>
                </label>
            </div>

            <label>
                Telefone
                <input type="tel" name="telefone" value="<?= e($telefone) ?>" data-phone-mask autocomplete="tel">
            </label>

            <button type="submit" class="btn primary">Cadastrar</button>

            <div class="auth-links">
                <a href="login.php">Já tenho conta</a>
            </div>
        </form>
    </main>
</body>
</html>
