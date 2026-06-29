<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$itens = ItemRepo::meusItens((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= favicon_head_tags() ?>
    <title>ReUse | Meus itens</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Gestão de anúncios</span>
                <h1 class="ops-title">Meus itens</h1>
                <p class="ops-copy">Visualize seus anúncios, pause publicações, reative itens e acompanhe o status de cada doação com mais clareza.</p>
            </div>
            <div class="ops-hero-side">
                <a class="btn primary" href="criar.php">Novo item</a>
            </div>
        </section>

        <?= flash_html() ?>

        <?php if (!$itens): ?>
            <div class="empty-card">Você ainda não cadastrou nenhum item. <a href="criar.php">Cadastrar agora</a>.</div>
        <?php endif; ?>

        <section class="list-grid">
            <?php foreach ($itens as $item): ?>
                <article class="list-card">
                    <div class="list-card-head">
                        <div>
                            <h2><?= e($item['titulo']) ?></h2>
                            <p class="list-card-meta"><?= e($item['categoria']) ?> · <?= (int) $item['pontos'] ?> pontos</p>
                        </div>
                        <span class="<?= e(status_badge_class((string) $item['status'])) ?>"><?= e(status_label((string) $item['status'])) ?></span>
                    </div>

                    <div class="list-card-actions">
                        <a class="btn" href="detalhe.php?id=<?= (int) $item['id'] ?>">Ver</a>

                        <?php if (in_array($item['status'], ['disponivel', 'pausado'], true)): ?>
                            <a class="btn secondary" href="editar.php?id=<?= (int) $item['id'] ?>">Editar</a>
                        <?php endif; ?>

                        <?php if ($item['status'] === 'disponivel'): ?>
                            <form method="post" action="acao.php" class="inline-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="acao" value="pausar">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn" type="submit">Pausar</button>
                            </form>
                        <?php elseif ($item['status'] === 'pausado'): ?>
                            <form method="post" action="acao.php" class="inline-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="acao" value="reativar">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn primary" type="submit">Reativar</button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($item['status'], ['disponivel', 'pausado'], true)): ?>
                            <form method="post" action="acao.php" class="inline-form" onsubmit="return confirm('Excluir este anúncio?');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn danger" type="submit">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
