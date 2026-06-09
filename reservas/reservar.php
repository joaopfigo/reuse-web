<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();
$itemId  = (int) ($_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    header('Location: ' . app_url('itens/listar.php'));
    exit;
}

$item = ItemRepo::buscarPorId($itemId);

if (!$item || $item['status'] !== 'disponivel') {
    flash_set('error', 'Este item não está mais disponível.');
    header('Location: ' . app_url('itens/listar.php'));
    exit;
}

if ((int) $item['doadora_id'] === (int) $usuario['id']) {
    flash_set('error', 'Você não pode reservar seu próprio item.');
    header('Location: ' . app_url('itens/detalhe.php?id=' . $itemId));
    exit;
}

// RF28: verificar bloqueio por no-show
if (!empty($usuario['bloqueada_ate']) && strtotime($usuario['bloqueada_ate']) > time()) {
    flash_set('error', 'Sua conta está temporariamente bloqueada por não comparecimento. Liberação em: ' . $usuario['bloqueada_ate']);
    header('Location: ' . app_url('itens/detalhe.php?id=' . $itemId));
    exit;
}

// RF22: verificar saldo de pontos
if ((int) $usuario['saldo_pontos'] < (int) $item['pontos']) {
    flash_set('error', 'Saldo insuficiente. Você tem ' . $usuario['saldo_pontos'] . ' pontos e o item custa ' . $item['pontos'] . ' pontos.');
    header('Location: ' . app_url('itens/detalhe.php?id=' . $itemId));
    exit;
}

$pdo = db();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('SELECT id FROM itens WHERE id = :id AND status = "disponivel" FOR UPDATE');
    $stmt->execute([':id' => $itemId]);

    if (!$stmt->fetch()) {
        $pdo->rollBack();
        flash_set('error', 'Este item acabou de ser reservado por outra pessoa.');
        header('Location: ' . app_url('itens/listar.php'));
        exit;
    }

    $reservaId = ReservaRepo::criar($itemId, (int) $usuario['id']);
    $pdo->prepare('UPDATE itens SET status = "reservado" WHERE id = :id')
        ->execute([':id' => $itemId]);

    $pdo->commit();

    // RF27: notificar doadora
    NotificacaoRepo::criar(
        (int) $item['doadora_id'],
        'reserva',
        $usuario['nome'] . ' reservou seu item "' . $item['titulo'] . '".',
        $reservaId
    );

    flash_set('success', 'Item reservado! Aguarde a doadora definir o local e horário de retirada.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Erro ao reservar. Tente novamente.');
    header('Location: ' . app_url('itens/detalhe.php?id=' . $itemId));
    exit;
}
