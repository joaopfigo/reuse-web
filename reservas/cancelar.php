<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();
exigir_post();
validar_csrf_post();

$reservaId = (int) ($_POST['id'] ?? 0);
$reserva = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

if (!$reserva || (int) $reserva['receptora_id'] !== (int) $usuario['id']) {
    flash_set('error', 'Reserva não encontrada.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;
}

if (!in_array($reserva['status'], ['pendente', 'aceita'], true)) {
    flash_set('error', 'Esta reserva não pode mais ser cancelada.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;
}

$pdo = db();
$pdo->beginTransaction();

try {
    $cancelou = ReservaRepo::cancelar($reservaId, (int) $usuario['id']);

    if ($cancelou) {
        $pdo->prepare('UPDATE itens SET status = "disponivel" WHERE id = :id')
            ->execute([':id' => $reserva['item_id']]);

        NotificacaoRepo::criar(
            (int) $reserva['doadora_id'],
            'cancelamento',
            $usuario['nome'] . ' cancelou a reserva do item "' . $reserva['titulo'] . '".',
            $reservaId
        );
    }

    $pdo->commit();
    flash_set('success', 'Reserva cancelada. O item voltou a ficar disponível.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Erro ao cancelar a reserva.');
}

header('Location: ' . app_url('reservas/minhas.php'));
exit;
