-- Dados demonstrativos para banca do ReUse
-- Senha dos usuarios demo: Reuse@2026
-- Execute apenas em ambiente de teste/apresentacao.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM notificacoes WHERE usuario_id BETWEEN 9001 AND 9099;
DELETE FROM avaliacoes WHERE avaliadora_id BETWEEN 9001 AND 9099 OR avaliada_id BETWEEN 9001 AND 9099;
DELETE FROM transacoes_pontos WHERE usuario_id BETWEEN 9001 AND 9099;
DELETE FROM reservas WHERE id BETWEEN 9001 AND 9099;
DELETE FROM item_fotos WHERE item_id BETWEEN 9001 AND 9099;
DELETE FROM itens WHERE id BETWEEN 9001 AND 9099;
DELETE FROM compras_pontos WHERE id BETWEEN 9001 AND 9099;
DELETE FROM usuarios WHERE id BETWEEN 9001 AND 9099;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO usuarios
    (id, nome, email, email_verificado_em, senha_hash, telefone, telefone_normalizado, bairro, cidade, saldo_pontos, no_show_count, ativo)
VALUES
    (9001, 'Ana Souza', 'ana.demo@reuse.local', NOW(), '$2y$10$ww0yXCYODvHugw/8tuYuY.rCG.HCoOLnI50dVlorOmuDvky/Wppsa', '(31) 90001-0001', '31900010001', 'Savassi', 'Belo Horizonte', 5, 0, 1),
    (9002, 'Beatriz Lima', 'beatriz.demo@reuse.local', NOW(), '$2y$10$ww0yXCYODvHugw/8tuYuY.rCG.HCoOLnI50dVlorOmuDvky/Wppsa', '(31) 90002-0002', '31900020002', 'Cruzeiro', 'Belo Horizonte', 5, 0, 1),
    (9003, 'Camila Rocha', 'camila.demo@reuse.local', NOW(), '$2y$10$ww0yXCYODvHugw/8tuYuY.rCG.HCoOLnI50dVlorOmuDvky/Wppsa', '(31) 90003-0003', '31900030003', 'Funcionarios', 'Belo Horizonte', 0, 1, 1);

INSERT INTO compras_pontos
    (id, usuario_id, quantidade_pontos, valor_base, taxa, valor_total, status, referencia_externa, mercado_pago_status, aprovado_em)
VALUES
    (9001, 9002, 10, 30.00, 1.50, 31.50, 'aprovado', 'demo_compra_beatriz_001', 'approved', NOW());

INSERT INTO itens
    (id, doadora_id, categoria_id, titulo, descricao, condicao, pontos, bairro, cidade, status)
VALUES
    (9001, 9001, 1, 'Vestido midi floral', 'Vestido em ótimo estado, tecido leve e ideal para uso casual.', 'seminovo', 8, 'Savassi', 'Belo Horizonte', 'disponivel'),
    (9002, 9001, 2, 'Bolsa transversal vinho', 'Bolsa pequena com alça regulável e poucos sinais de uso.', 'usado_bom', 6, 'Savassi', 'Belo Horizonte', 'disponivel'),
    (9003, 9001, 4, 'Livro de algoritmos', 'Livro usado em bom estado para estudos de programação.', 'usado_bom', 5, 'Savassi', 'Belo Horizonte', 'entregue'),
    (9004, 9002, 5, 'Vaso decorativo ceramico', 'Vaso pequeno para decoração, sem trincas.', 'usado_bom', 4, 'Cruzeiro', 'Belo Horizonte', 'disponivel'),
    (9005, 9003, 3, 'Hidratante lacrado', 'Produto novo, lacrado e dentro da validade.', 'novo', 7, 'Funcionarios', 'Belo Horizonte', 'disponivel');

INSERT INTO reservas
    (id, item_id, receptora_id, status, expira_em, local_retirada, data_retirada, observacao, codigo_confirmacao, criada_em, atualizada_em)
VALUES
    (9001, 9003, 9002, 'entregue', DATE_ADD(NOW(), INTERVAL 2 DAY), 'Biblioteca pública da região', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Entrega concluída para demonstração.', 'A1B2C3', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW());

INSERT INTO transacoes_pontos
    (usuario_id, reserva_id, compra_pontos_id, tipo, quantidade, motivo)
VALUES
    (9002, NULL, 9001, 'credito', 10, 'Compra de pontos aprovada'),
    (9001, 9001, NULL, 'credito', 5, 'Credito por entrega confirmada'),
    (9002, 9001, NULL, 'debito', 5, 'Debito por recebimento confirmado');

INSERT INTO avaliacoes
    (id, reserva_id, avaliadora_id, avaliada_id, nota, comentario)
VALUES
    (9001, 9001, 9002, 9001, 5, 'Entrega organizada, item exatamente como anunciado.'),
    (9002, 9001, 9001, 9002, 5, 'Retirada pontual e comunicação clara.');

INSERT INTO notificacoes
    (usuario_id, tipo, mensagem, lida, reserva_id)
VALUES
    (9001, 'confirmacao', 'Entrega do livro de algoritmos confirmada com sucesso.', 0, 9001),
    (9002, 'pontos', 'Sua compra de 10 pontos foi aprovada.', 0, NULL),
    (9002, 'avaliacao', 'Você recebeu uma avaliação positiva.', 1, 9001);
