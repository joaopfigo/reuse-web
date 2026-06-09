<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario   = exigir_login();
$reservaId = (int) ($_GET['id'] ?? 0);
$reserva   = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

if (!$reserva
    || (int) $reserva['doadora_id'] !== (int) $usuario['id']
    || $reserva['status'] !== 'aceita'
) {
    flash_set('error', 'Operação inválida.');
    header('Location: ' . app_url('reservas/gerenciar.php'));
    exit;
}

$pdo = db();
$pdo->beginTransaction();

try {
    $pdo->prepare('UPDATE reservas SET status = "no_show", atualizada_em = NOW() WHERE id = :id')
        ->execute([':id' => $reservaId]);
    $pdo->prepare('UPDATE itens SET status = "disponivel" WHERE id = :id')
        ->execute([':id' => $reserva['item_id']]);

    $pdo->prepare('UPDATE usuarios SET no_show_count = no_show_count + 1 WHERE id = :id')
        ->execute([':id' => $reserva['receptora_id']]);

    $stmt = $pdo->prepare('SELECT no_show_count FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $reserva['receptora_id']]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= 2) {
        $pdo->prepare(
            'UPDATE usuarios SET bloqueada_ate = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = :id'
        )->execute([':id' => $reserva['receptora_id']]);

        NotificacaoRepo::criar(
            (int) $reserva['receptora_id'],
            'noshow',
            'Sua conta foi bloqueada por 7 dias devido a não comparecimentos repetidos.',
            $reservaId
        );
    }

    $pdo->commit();
    flash_set('success', 'Não comparecimento registrado. O item voltou para disponível.');

} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Erro ao registrar não comparecimento.');
}

header('Location: ' . app_url('reservas/gerenciar.php'));
exit;
