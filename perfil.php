<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/layout.php';
require_once __DIR__ . '/app/repositories/UsuarioRepo.php';

$usuario = exigir_login();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '') ?: null;
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? 'Belo Horizonte') ?: 'Belo Horizonte';

    if (!$nome || !$bairro) {
        $erro = 'Nome e bairro sao obrigatorios.';
    } else {
        UsuarioRepo::atualizarPerfil((int) $usuario['id'], $nome, $telefone, $bairro, $cidade);
        $usuario = exigir_login();
        $sucesso = 'Perfil atualizado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Perfil</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <?php if (!empty($usuario['bloqueada_ate']) && strtotime($usuario['bloqueada_ate']) > time()): ?>
            <div class="panel" style="margin-bottom: 1rem;">
                <div class="alert error">
                    Sua conta está temporariamente bloqueada por não comparecimentos repetidos.
                    A restrição é liberada em:
                    <strong><?= e(date('d/m/Y \à\s H:i', strtotime($usuario['bloqueada_ate']))) ?></strong>.
                    Durante este período não é possível fazer novas reservas.
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="panel stack">
            <h1>Perfil</h1>

            <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

            <div class="grid-2">
                <label>
                    Nome
                    <input type="text" name="nome" value="<?= e($usuario['nome']) ?>" required>
                </label>

                <label>
                    Telefone
                    <input type="tel" name="telefone" value="<?= e($usuario['telefone']) ?>">
                </label>
            </div>

            <div class="grid-2">
                <label>
                    Bairro
                    <input type="text" name="bairro" value="<?= e($usuario['bairro']) ?>" required>
                </label>

                <label>
                    Cidade
                    <input type="text" name="cidade" value="<?= e($usuario['cidade']) ?>" required>
                </label>
            </div>

            <button class="btn primary" type="submit">Salvar perfil</button>
        </form>
    </main>
</body>
</html>
