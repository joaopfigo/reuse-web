<?php
require_once __DIR__ . '/app/helpers/auth.php';

$_SESSION = [];
session_destroy();

header('Location: ' . app_url('login.php'));
exit;
