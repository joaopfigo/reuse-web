-- ReUse - verificacao de conta, telefone unico e validacao de duplicidade

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS email_verificado_em DATETIME NULL AFTER email,
    ADD COLUMN IF NOT EXISTS telefone_normalizado VARCHAR(20) NULL AFTER telefone;

UPDATE usuarios
SET telefone_normalizado = REGEXP_REPLACE(telefone, '[^0-9]', '')
WHERE telefone IS NOT NULL
  AND telefone <> ''
  AND (telefone_normalizado IS NULL OR telefone_normalizado = '');

UPDATE usuarios u
JOIN (
    SELECT telefone_normalizado, MIN(id) AS manter_id
    FROM usuarios
    WHERE telefone_normalizado IS NOT NULL
      AND telefone_normalizado <> ''
    GROUP BY telefone_normalizado
    HAVING COUNT(*) > 1
) repetidos ON repetidos.telefone_normalizado = u.telefone_normalizado
SET u.telefone_normalizado = NULL
WHERE u.id <> repetidos.manter_id;

ALTER TABLE usuarios
    ADD UNIQUE KEY uk_usuarios_telefone_normalizado (telefone_normalizado);

CREATE TABLE IF NOT EXISTS email_confirmacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_confirmacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_email_confirmacoes_usuario (usuario_id),
    INDEX idx_email_confirmacoes_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE item_fotos
    ADD COLUMN IF NOT EXISTS hash_perceptual CHAR(16) NULL AFTER caminho;

CREATE INDEX idx_item_fotos_hash_perceptual ON item_fotos (hash_perceptual);
CREATE INDEX idx_itens_doadora_status_categoria ON itens (doadora_id, status, categoria_id, condicao);
