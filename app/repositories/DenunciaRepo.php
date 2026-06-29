<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DenunciaRepo
{
    public static function criar(
        int $denuncianteId,
        string $motivo,
        string $descricao,
        ?int $itemId = null,
        ?int $reservaId = null,
        ?int $denunciadaId = null,
        ?string $evidenciaPath = null
    ): void {
        $colunas = ['denunciante_id', 'item_id', 'reserva_id', 'denunciada_id', 'motivo', 'descricao'];
        $valores = [':denunciante_id', ':item_id', ':reserva_id', ':denunciada_id', ':motivo', ':descricao'];
        $params = [
            ':denunciante_id' => $denuncianteId,
            ':item_id'        => $itemId,
            ':reserva_id'     => $reservaId,
            ':denunciada_id'  => $denunciadaId,
            ':motivo'         => $motivo,
            ':descricao'      => $descricao,
        ];

        if ($evidenciaPath !== null && self::colunaExiste('evidencia_caminho')) {
            $colunas[] = 'evidencia_caminho';
            $valores[] = ':evidencia_caminho';
            $params[':evidencia_caminho'] = $evidenciaPath;
        } elseif ($evidenciaPath !== null) {
            $params[':descricao'] .= "\n\nEvidência anexada: " . $evidenciaPath;
        }

        $sql = 'INSERT INTO denuncias (' . implode(', ', $colunas) . ')
                VALUES (' . implode(', ', $valores) . ')';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    }

    private static function colunaExiste(string $coluna): bool
    {
        static $cache = [];

        if (array_key_exists($coluna, $cache)) {
            return $cache[$coluna];
        }

        $stmt = db()->prepare('SHOW COLUMNS FROM denuncias LIKE :coluna');
        $stmt->execute([':coluna' => $coluna]);
        $cache[$coluna] = (bool) $stmt->fetch();

        return $cache[$coluna];
    }

    public static function estatisticasPublicas(int $usuarioId): array
    {
        $stmtTotal = db()->prepare('SELECT COUNT(*) FROM denuncias WHERE denunciada_id = :usuario_id');
        $stmtTotal->execute([':usuario_id' => $usuarioId]);

        $stmtMotivos = db()->prepare(
            'SELECT motivo, COUNT(*) AS total
             FROM denuncias
             WHERE denunciada_id = :usuario_id
             GROUP BY motivo
             ORDER BY total DESC, motivo ASC'
        );
        $stmtMotivos->execute([':usuario_id' => $usuarioId]);

        return [
            'total' => (int) $stmtTotal->fetchColumn(),
            'motivos' => $stmtMotivos->fetchAll(),
        ];
    }
}
