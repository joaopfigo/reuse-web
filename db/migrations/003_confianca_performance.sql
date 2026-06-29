-- Melhorias de confiança, evidência de denúncia e performance.
-- Execute uma vez no banco da Hostinger pelo phpMyAdmin, com o banco do ReUse selecionado.

ALTER TABLE usuarios
    MODIFY saldo_pontos INT NOT NULL DEFAULT 0;

ALTER TABLE denuncias
    ADD COLUMN evidencia_caminho VARCHAR(255) NULL AFTER descricao;

CREATE INDEX idx_itens_feed ON itens (status, categoria_id, condicao, criado_em);
CREATE INDEX idx_itens_local ON itens (bairro, cidade);
CREATE INDEX idx_reservas_receptora_status ON reservas (receptora_id, status, criada_em);
CREATE INDEX idx_reservas_status_expira ON reservas (status, expira_em);
CREATE INDEX idx_notificacoes_usuario_lida ON notificacoes (usuario_id, lida, criada_em);
CREATE INDEX idx_chat_reserva_criada ON chat_mensagens (reserva_id, criada_em);
CREATE INDEX idx_avaliacoes_avaliada ON avaliacoes (avaliada_id, criada_em);
CREATE INDEX idx_denuncias_denunciada ON denuncias (denunciada_id, motivo, criada_em);
