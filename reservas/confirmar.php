<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/services/PontosService.php';

exigir_login();
$reservaId = (int) ($_GET['id'] ?? 0);

if ($reservaId > 0) {
    PontosService::confirmarEntrega($reservaId);
}

header('Location: ' . app_url('pontos/carteira.php'));
exit;
