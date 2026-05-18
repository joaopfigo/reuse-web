<?php
require_once __DIR__ . '/app/helpers/auth.php';

if (usuario_logado()) {
    header('Location: itens/listar.php');
    exit;
}

header('Location: login.php');
exit;
