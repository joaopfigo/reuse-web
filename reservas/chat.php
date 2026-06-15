<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();
$reservaId = (int) ($_GET['id'] ?? 0);
$reserva = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

$ehReceptora = $reserva && (int) $reserva['receptora_id'] === (int) $usuario['id'];
$ehDoadora = $reserva && (int) $reserva['doadora_id'] === (int) $usuario['id'];

if (!$reserva || (!$ehReceptora && !$ehDoadora)) {
    flash_set('error', 'Acesso negado.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $mensagem = trim($_POST['mensagem'] ?? '');
    if ($mensagem !== '') {
        $stmt = db()->prepare(
            'INSERT INTO chat_mensagens (reserva_id, remetente_id, mensagem)
             VALUES (:reserva_id, :remetente_id, :mensagem)'
        );
        $stmt->execute([
            ':reserva_id' => $reservaId,
            ':remetente_id' => $usuario['id'],
            ':mensagem' => $mensagem,
        ]);
    }
    header('Location: ' . app_url('reservas/chat.php?id=' . $reservaId));
    exit;
}

$stmt = db()->prepare(
    'SELECT m.mensagem, m.criada_em, u.nome AS remetente, u.id AS remetente_id
     FROM chat_mensagens m
     JOIN usuarios u ON u.id = m.remetente_id
     WHERE m.reserva_id = :reserva_id
     ORDER BY m.criada_em ASC'
);
$stmt->execute([':reserva_id' => $reservaId]);
$mensagens = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Chat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <div class="page-header">
                <div>
                    <h1>Mensagens · <?= e($reserva['titulo']) ?></h1>
                    <p class="muted">
                        Doadora: <?= e($reserva['doadora_nome']) ?> ·
                        Receptora: <?= e($reserva['receptora_nome']) ?>
                    </p>
                </div>
                <a href="<?= $ehDoadora ? 'gerenciar.php' : 'minhas.php' ?>" class="btn">Voltar</a>
            </div>

            <div class="chat-toolbar muted">Use o chat para alinhar detalhes rápidos sobre a retirada do item.</div>

            <div class="chat-messages" data-auto-refresh>
                <?php if (!$mensagens): ?>
                    <p class="muted empty-state">Nenhuma mensagem ainda.</p>
                <?php endif; ?>
                <?php foreach ($mensagens as $m): ?>
                    <?php $propria = (int) $m['remetente_id'] === (int) $usuario['id']; ?>
                    <div class="msg <?= $propria ? 'msg-own' : 'msg-other' ?>">
                        <span class="msg-sender"><?= e($m['remetente']) ?></span>
                        <span class="msg-text"><?= e($m['mensagem']) ?></span>
                        <span class="msg-time"><?= e(formatar_data_hora($m['criada_em'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" class="form-card">
                <?= csrf_input() ?>
                <label>
                    <textarea name="mensagem" rows="2" required maxlength="500" placeholder="Escreva sua mensagem..."></textarea>
                </label>
                <div class="action-row">
                    <button type="submit" class="btn primary">Enviar</button>
                    <a href="<?= $ehDoadora ? 'gerenciar.php' : 'minhas.php' ?>" class="btn">Voltar</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
