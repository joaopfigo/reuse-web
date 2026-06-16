<?php

declare(strict_types=1);

function db_config(): array
{
    $config = [
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'reuse',
        'user' => 'root',
        'password' => '',
    ];

    $arquivosCredenciais = [
        dirname(__DIR__, 3) . '/private_config/db.credentials.php',
        __DIR__ . '/db.credentials.php',
    ];

    foreach ($arquivosCredenciais as $arquivoCredenciais) {
        if (is_readable($arquivoCredenciais)) {
            try {
                $credenciais = require $arquivoCredenciais;
            } catch (Throwable $erroCredenciais) {
                continue;
            }

            if (is_array($credenciais)) {
                foreach ($credenciais as $chave => $valor) {
                    if ($valor !== null && $valor !== '') {
                        $config[$chave] = $valor;
                    }
                }
            }
            break;
        }
    }

    $mapaEnv = [
        'host' => 'DB_HOST',
        'port' => 'DB_PORT',
        'dbname' => 'DB_NAME',
        'user' => 'DB_USER',
        'password' => 'DB_PASSWORD',
    ];

    foreach ($mapaEnv as $chave => $variavel) {
        $valor = getenv($variavel);
        if ($valor !== false && $valor !== '') {
            $config[$chave] = $valor;
        }
    }

    return $config;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = db_config();
    $host = $config['host'];
    $port = $config['port'];
    $dbname = $config['dbname'];
    $user = $config['user'];
    $password = $config['password'];

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $erro) {
        throw new RuntimeException(
            'Nao foi possivel conectar ao banco de dados. Verifique host, nome do banco, usuario e senha configurados para este ambiente.',
            0,
            $erro
        );
    }

    return $pdo;
}
