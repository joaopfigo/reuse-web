<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario  = exigir_login();
$reservas = ReservaRepo::minhasDaDoadora((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Reservas dos meus itens</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Reservas dos meus itens</h1>
            <?= flash_html() ?>

            <?php if (!$reservas): ?>
                <p class="muted">Nenhuma reserva pendente nos seus itens.</p>
            <?php endif; ?>

            <?php foreach ($reservas as $r): ?>
                <article class="panel">
                    <h2><?= e($r['titulo']) ?></h2>
                    <p class="muted">
                        Receptora: <strong><?= e($r['receptora_nome']) ?></strong> |
                        <?= (int) $r['pontos'] ?> pontos |
                        Status: <?= e($r['status']) ?>
                    </p>
                    <p class="muted">Expira em: <?= e($r['expira_em']) ?></p>

                    <?php if ($r['status'] === 'pendente'): ?>
                        <a class="btn primary" href="aceitar.php?id=<?= (int) $r['id'] ?>">
                            Aceitar e definir local/horário
                        </a>
                    <?php else: ?>
                        <p>
                            Local: <strong><?= e($r['local_retirada']) ?></strong><br>
                            Data: <strong><?= e($r['data_retirada']) ?></strong>
                        </p>
                        <?php if ($r['codigo_confirmacao']): ?>
                            <p>Código de confirmação: <code><?= e($r['codigo_confirmacao']) ?></code></p>
                            <p class="muted">Mostre este código para a receptora no momento da entrega.</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <a class="btn" href="chat.php?id=<?= (int) $r['id'] ?>">Mensagens</a>

                    <?php
                    $reuniaoPassou = !empty($r['data_retirada']) && strtotime($r['data_retirada']) < time();
                    $expirou       = strtotime($r['expira_em']) < time();
                    if ($r['status'] === 'aceita' && ($reuniaoPassou || $expirou)): ?>
                        <a class="btn danger"
                           href="noshow.php?id=<?= (int) $r['id'] ?>"
                           onclick="return confirm('Confirmar que a receptora não compareceu? Isso será registrado e pode resultar no bloqueio temporário da conta dela.')">
                            Registrar não comparecimento
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
