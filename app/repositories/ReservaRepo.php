<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class ReservaRepo
{
    public static function criar(int $itemId, int $receptoraId): int
    {
        $codigo = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $stmt = db()->prepare(
            'INSERT INTO reservas (item_id, receptora_id, expira_em, codigo_confirmacao)
             VALUES (:item_id, :receptora_id, DATE_ADD(NOW(), INTERVAL 24 HOUR), :codigo)'
        );
        $stmt->execute([
            ':item_id'      => $itemId,
            ':receptora_id' => $receptoraId,
            ':codigo'       => $codigo,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT r.*,
                    i.titulo, i.pontos, i.doadora_id, i.id AS item_id,
                    rec.nome AS receptora_nome,
                    doe.nome AS doadora_nome
             FROM reservas r
             JOIN itens i     ON i.id = r.item_id
             JOIN usuarios rec ON rec.id = r.receptora_id
             JOIN usuarios doe ON doe.id = i.doadora_id
             WHERE r.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function minhasDaReceptora(int $usuarioId): array
    {
        $stmt = db()->prepare(
            'SELECT r.*, i.titulo, i.pontos, i.doadora_id, doe.nome AS doadora
             FROM reservas r
             JOIN itens i      ON i.id = r.item_id
             JOIN usuarios doe ON doe.id = i.doadora_id
             WHERE r.receptora_id = :usuario_id
             ORDER BY r.criada_em DESC'
        );
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public static function minhasDaDoadora(int $usuarioId): array
    {
        $stmt = db()->prepare(
            'SELECT r.*, i.titulo, i.pontos, rec.nome AS receptora_nome
             FROM reservas r
             JOIN itens i      ON i.id = r.item_id
             JOIN usuarios rec ON rec.id = r.receptora_id
             WHERE i.doadora_id = :usuario_id
               AND r.status IN ("pendente", "aceita")
             ORDER BY r.criada_em DESC'
        );
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public static function cancelar(int $id, int $receptoraId): bool
    {
        $stmt = db()->prepare(
            'UPDATE reservas
             SET status = "cancelada", atualizada_em = NOW()
             WHERE id = :id AND receptora_id = :receptora_id AND status IN ("pendente","aceita")'
        );
        $stmt->execute([':id' => $id, ':receptora_id' => $receptoraId]);
        return $stmt->rowCount() > 0;
    }

    public static function aceitar(int $id, int $doadoraId, string $localRetirada, string $dataRetirada): bool
    {
        $stmt = db()->prepare(
            'UPDATE reservas r
             JOIN itens i ON i.id = r.item_id
             SET r.status = "aceita",
                 r.local_retirada = :local,
                 r.data_retirada = :data,
                 r.atualizada_em = NOW()
             WHERE r.id = :id AND i.doadora_id = :doadora_id AND r.status = "pendente"'
        );
        $stmt->execute([
            ':id'        => $id,
            ':doadora_id'=> $doadoraId,
            ':local'     => $localRetirada,
            ':data'      => $dataRetirada,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function expirarVencidas(): void
    {
        // Apenas reservas ainda PENDENTES (não tratadas pela doadora) expiram
        // automaticamente. Reservas ACEITAS que furam o combinado são resolvidas
        // pelo fluxo manual de no-show (RF28), sob controle da doadora.
        db()->exec(
            'UPDATE reservas r
             JOIN itens i ON i.id = r.item_id
             SET r.status = "expirada", i.status = "disponivel"
             WHERE r.status = "pendente" AND r.expira_em < NOW()'
        );
    }
}
