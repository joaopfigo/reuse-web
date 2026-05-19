<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/services/ReservaService.php';
require_once __DIR__ . '/../app/config/db.php';

$usuario = exigir_login();
$itemId = (int) ($_GET['item_id'] ?? 0);

if ($itemId > 0) {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT * FROM itens WHERE id = :id AND status = "disponivel" FOR UPDATE');
        $stmt->execute([':id' => $itemId]);
        $item = $stmt->fetch();

        if ($item && (int) $item['doadora_id'] !== (int) $usuario['id']) {
            $reserva = $pdo->prepare('INSERT INTO reservas (item_id, receptora_id, expira_em) VALUES (:item_id, :receptora_id, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
            $reserva->execute([
                ':item_id' => $itemId,
                ':receptora_id' => $usuario['id'],
            ]);

            $pdo->prepare('UPDATE itens SET status = "reservado" WHERE id = :id')->execute([':id' => $itemId]);
        }

        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
}

header('Location: ' . app_url('reservas/minhas.php'));
exit;
