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
$cidade = trim((string) ($_POST['cidade'] ?? 'Belo Horizonte'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $senha = $_POST['senha'] ?? '';
    $telefoneRepo = $telefone !== '' ? $telefone : null;
    $telefoneNormalizado = normalizar_telefone($telefoneRepo);

    if (!$nome || !validar_email($email) || strlen($senha) < 8 || !$bairro || !$cidade) {
        $erro = 'Preencha nome, e-mail valido, senha com 8 caracteres, bairro e cidade.';
    } elseif ($telefoneNormalizado === null || strlen($telefoneNormalizado) < 10) {
        $erro = 'Informe um telefone valido com DDD.';
    } else {
        try {
            $resultado = AuthService::cadastrar($nome, $email, $senha, $telefoneRepo, $telefoneNormalizado, $bairro, $cidade);
            $sucesso = $resultado['email_enviado']
                ? 'Cadastro criado. Enviamos um link para confirmar seu e-mail antes de publicar itens ou fazer reservas.'
                : 'Cadastro criado, mas o e-mail de confirmacao nao foi enviado. Configure o SMTP e use a opcao de reenviar confirmacao no perfil.';
            $nome = '';
            $email = '';
            $telefone = '';
            $bairro = '';
            $cidade = 'Belo Horizonte';
        } catch (PDOException $e) {
            $erro = ((string) $e->getCode() === '23000')
                ? 'Este e-mail ou telefone ja esta cadastrado.'
                : 'Nao foi possivel concluir o cadastro agora.';
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
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
    <?= pwa_head_tags() ?>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <span class="hero-tag">Entrada na comunidade</span>
            <h1>Crie sua conta e participe de uma rede de reuso mais organizada.</h1>
            <p>Cadastre-se para publicar itens, reservar doacoes, acompanhar notificacoes e movimentar sua carteira de pontos com seguranca.</p>

            <div class="auth-feature-list">
                <div class="auth-feature">
                    <strong>Cadastro simples</strong>
                    <span>E-mail e telefone unicos ajudam a reduzir contas duplicadas e deixam a comunidade mais confiavel.</span>
                </div>
                <div class="auth-feature">
                    <strong>Conta verificada</strong>
                    <span>Depois de confirmar o e-mail, voce libera publicacoes e reservas. Pontos entram por entregas confirmadas ou compra opcional.</span>
                </div>
            </div>
        </section>

        <form method="post" class="auth-panel">
            <div class="section-header">
                <h2>Criar cadastro</h2>
                <p>Abra sua conta para comecar a doar, reservar e acompanhar toda a jornada no ReUse.</p>
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
                    Telefone
                    <input type="tel" name="telefone" value="<?= e($telefone) ?>" data-phone-mask autocomplete="tel" required>
                </label>
            </div>

            <div class="grid-2">
                <label>
                    Bairro
                    <input type="text" name="bairro" value="<?= e($bairro) ?>" autocomplete="address-level2" required>
                </label>

                <label>
                    Cidade
                    <input type="text" name="cidade" value="<?= e($cidade) ?>" autocomplete="address-level1" required>
                </label>
            </div>

            <button type="submit" class="btn primary">Cadastrar</button>

            <div class="auth-links">
                <a href="login.php">Ja tenho conta</a>
            </div>
        </form>
    </main>
</body>
</html>
