<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/validar.php';
require_once __DIR__ . '/app/services/AuthService.php';

redirecionar_se_logado();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '') ?: null;
    $bairro = trim($_POST['bairro'] ?? '');

    if (!$nome || !validar_email($email) || strlen($senha) < 8 || !$bairro) {
        $erro = 'Preencha nome, e-mail valido, senha com 8 caracteres e bairro.';
    } else {
        try {
            AuthService::cadastrar($nome, $email, $senha, $telefone, $bairro);
            $sucesso = 'Cadastro criado. Agora voce pode fazer login.';
        } catch (PDOException $e) {
            $erro = 'Este e-mail ja esta cadastrado.';
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
</head>
<body class="auth-page">
    <main class="auth-card">
        <section class="auth-brand">
            <h1>ReUse</h1>
            <p>Crie sua conta para doar e encontrar itens disponiveis.</p>
        </section>

        <form method="post" class="form-card">
            <h2>Criar cadastro</h2>

            <?php if ($erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alert success"><?= e($sucesso) ?></div>
            <?php endif; ?>

            <label>
                Nome
                <input type="text" name="nome" required>
            </label>

            <label>
                E-mail
                <input type="email" name="email" required>
            </label>

            <div class="grid-2">
                <label>
                    Senha
                    <input type="password" name="senha" minlength="8" required>
                </label>

                <label>
                    Bairro
                    <input type="text" name="bairro" required>
                </label>
            </div>

            <label>
                Telefone
                <input type="tel" name="telefone">
            </label>

            <button type="submit" class="btn primary">Cadastrar</button>

            <p class="links">
                <a href="login.php">Ja tenho conta</a>
            </p>
        </form>
    </main>
</body>
</html>
