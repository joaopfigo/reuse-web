<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class ItemRepo
{
    public static function categorias(): array
    {
        return db()->query(
            'SELECT id, nome
             FROM categorias
             WHERE ativa = 1
               AND id IN (1, 2, 3, 4, 5)
             ORDER BY CASE id
                 WHEN 1 THEN 1
                 WHEN 2 THEN 2
                 WHEN 3 THEN 3
                 WHEN 4 THEN 4
                 WHEN 5 THEN 5
                 ELSE 99
             END'
        )->fetchAll();
    }

    public static function criar(array $dados, ?string $fotoPath): int
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $sql = 'INSERT INTO itens (doadora_id, categoria_id, titulo, descricao, condicao, pontos, bairro)
                    VALUES (:doadora_id, :categoria_id, :titulo, :descricao, :condicao, :pontos, :bairro)';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':doadora_id' => $dados['doadora_id'],
                ':categoria_id' => $dados['categoria_id'],
                ':titulo' => $dados['titulo'],
                ':descricao' => $dados['descricao'],
                ':condicao' => $dados['condicao'],
                ':pontos' => $dados['pontos'],
                ':bairro' => $dados['bairro'],
            ]);

            $itemId = (int) $pdo->lastInsertId();

            if ($fotoPath) {
                $foto = $pdo->prepare('INSERT INTO item_fotos (item_id, caminho, ordem) VALUES (:item_id, :caminho, 1)');
                $foto->execute([
                    ':item_id' => $itemId,
                    ':caminho' => $fotoPath,
                ]);
            }

            $pdo->commit();
            return $itemId;
        } catch (Throwable $erro) {
            $pdo->rollBack();
            throw $erro;
        }
    }

    public static function listar(array $filtros = []): array
    {
        $where = ['i.status = "disponivel"'];
        $params = [];

        if (!empty($filtros['q'])) {
            $where[] = '(i.titulo LIKE :q OR i.descricao LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        if (!empty($filtros['categoria_id'])) {
            $where[] = 'i.categoria_id = :categoria_id';
            $params[':categoria_id'] = $filtros['categoria_id'];
        }

        if (!empty($filtros['condicao'])) {
            $where[] = 'i.condicao = :condicao';
            $params[':condicao'] = $filtros['condicao'];
        }

        $sql = 'SELECT i.*, c.nome AS categoria, u.nome AS doadora, f.caminho AS foto
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                JOIN usuarios u ON u.id = i.doadora_id
                LEFT JOIN item_fotos f ON f.item_id = i.id AND f.ordem = 1
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY i.criado_em DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT i.*, c.nome AS categoria, u.nome AS doadora, f.caminho AS foto
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                JOIN usuarios u ON u.id = i.doadora_id
                LEFT JOIN item_fotos f ON f.item_id = i.id AND f.ordem = 1
                WHERE i.id = :id';

        $stmt = db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        return $item ?: null;
    }

    public static function atualizar(int $id, int $doadoraId, array $dados): bool
    {
        $sql = 'UPDATE itens
                SET categoria_id = :categoria_id,
                    titulo = :titulo,
                    descricao = :descricao,
                    condicao = :condicao,
                    pontos = :pontos,
                    bairro = :bairro,
                    atualizado_em = NOW()
                WHERE id = :id AND doadora_id = :doadora_id';

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':doadora_id' => $doadoraId,
            ':categoria_id' => $dados['categoria_id'],
            ':titulo' => $dados['titulo'],
            ':descricao' => $dados['descricao'],
            ':condicao' => $dados['condicao'],
            ':pontos' => $dados['pontos'],
            ':bairro' => $dados['bairro'],
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function meusItens(int $doadoraId): array
    {
        $sql = 'SELECT i.*, c.nome AS categoria, f.caminho AS foto
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                LEFT JOIN item_fotos f ON f.item_id = i.id AND f.ordem = 1
                WHERE i.doadora_id = :doadora_id AND i.status <> "cancelado"
                ORDER BY i.criado_em DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute([':doadora_id' => $doadoraId]);

        return $stmt->fetchAll();
    }

    public static function pausar(int $id, int $doadoraId): bool
    {
        $stmt = db()->prepare(
            'UPDATE itens SET status = "pausado", atualizado_em = NOW()
             WHERE id = :id AND doadora_id = :doadora_id AND status = "disponivel"'
        );
        $stmt->execute([':id' => $id, ':doadora_id' => $doadoraId]);

        return $stmt->rowCount() > 0;
    }

    public static function reativar(int $id, int $doadoraId): bool
    {
        $stmt = db()->prepare(
            'UPDATE itens SET status = "disponivel", atualizado_em = NOW()
             WHERE id = :id AND doadora_id = :doadora_id AND status = "pausado"'
        );
        $stmt->execute([':id' => $id, ':doadora_id' => $doadoraId]);

        return $stmt->rowCount() > 0;
    }

    public static function excluir(int $id, int $doadoraId): bool
    {
        // Soft-delete: marca como cancelado, preservando o historico de
        // reservas/pontos. So permite excluir itens nao reservados nem entregues.
        $stmt = db()->prepare(
            'UPDATE itens SET status = "cancelado", atualizado_em = NOW()
             WHERE id = :id AND doadora_id = :doadora_id AND status IN ("disponivel","pausado")'
        );
        $stmt->execute([':id' => $id, ':doadora_id' => $doadoraId]);

        return $stmt->rowCount() > 0;
    }
}
