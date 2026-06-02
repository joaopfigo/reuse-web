<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$id      = (int) ($_GET['id'] ?? 0);
$acao    = $_GET['acao'] ?? '';

$resultado = false;
$msgOk     = '';

switch ($acao) {
    case 'pausar':
        $resultado = ItemRepo::pausar($id, (int) $usuario['id']);
        $msgOk = 'Anúncio pausado. Ele não aparece mais no feed.';
        break;
    case 'reativar':
        $resultado = ItemRepo::reativar($id, (int) $usuario['id']);
        $msgOk = 'Anúncio reativado e disponível novamente.';
        break;
    case 'excluir':
        $resultado = ItemRepo::excluir($id, (int) $usuario['id']);
        $msgOk = 'Anúncio excluído.';
        break;
    default:
        flash_set('error', 'Ação inválida.');
        header('Location: ' . app_url('itens/meus.php'));
        exit;
}

if ($resultado) {
    flash_set('success', $msgOk);
} else {
    flash_set('error', 'Não foi possível concluir a ação. Anúncios reservados ou já entregues não podem ser pausados ou excluídos.');
}

header('Location: ' . app_url('itens/meus.php'));
exit;
