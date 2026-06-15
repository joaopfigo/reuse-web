<?php
require_once __DIR__ . '/app/helpers/auth.php';

exigir_post();
validar_csrf_post();

$_SESSION = [];
session_destroy();

header('Location: ' . app_url('login.php'));
exit;
