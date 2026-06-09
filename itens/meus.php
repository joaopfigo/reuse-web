<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$itens   = ItemRepo::meusItens((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Meus itens</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Meus itens</h1>
            <?= flash_html() ?>

            <?php if (!$itens): ?>
                <p class="muted">Você ainda não cadastrou nenhum item.
                    <a href="criar.php">Cadastrar agora</a>.</p>
            <?php endif; ?>

            <?php foreach ($itens as $item): ?>
                <article class="panel">
                    <h2><?= e($item['titulo']) ?></h2>
                    <p class="muted">
                        <?= e($item['categoria']) ?> |
                        <?= (int) $item['pontos'] ?> pontos |
                        Status: <strong><?= e($item['status']) ?></strong>
                    </p>

                    <a class="btn" href="detalhe.php?id=<?= (int) $item['id'] ?>">Ver</a>

                    <?php if (in_array($item['status'], ['disponivel', 'pausado'], true)): ?>
                        <a class="btn secondary" href="editar.php?id=<?= (int) $item['id'] ?>">Editar</a>
                    <?php endif; ?>

                    <?php if ($item['status'] === 'disponivel'): ?>
                        <a class="btn" href="acao.php?acao=pausar&id=<?= (int) $item['id'] ?>">Pausar</a>
                    <?php elseif ($item['status'] === 'pausado'): ?>
                        <a class="btn primary" href="acao.php?acao=reativar&id=<?= (int) $item['id'] ?>">Reativar</a>
                    <?php endif; ?>

                    <?php if (in_array($item['status'], ['disponivel', 'pausado'], true)): ?>
                        <a class="btn danger" href="acao.php?acao=excluir&id=<?= (int) $item['id'] ?>"
                           onclick="return confirm('Excluir este anúncio?')">Excluir</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
