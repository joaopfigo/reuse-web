-- ReUse - compra opcional de pontos via Mercado Pago Checkout Pro

ALTER TABLE usuarios
    MODIFY saldo_pontos INT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS compras_pontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    quantidade_pontos INT NOT NULL,
    valor_base DECIMAL(10,2) NOT NULL,
    taxa DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'criada',
    referencia_externa VARCHAR(80) NOT NULL UNIQUE,
    mercado_pago_preference_id VARCHAR(120) NULL,
    mercado_pago_payment_id VARCHAR(120) NULL,
    mercado_pago_status VARCHAR(60) NULL,
    mercado_pago_status_detail VARCHAR(180) NULL,
    mercado_pago_init_point TEXT NULL,
    mercado_pago_sandbox_init_point TEXT NULL,
    aprovado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_compras_pontos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_compras_pontos_usuario (usuario_id),
    INDEX idx_compras_pontos_status (status),
    INDEX idx_compras_pontos_preference (mercado_pago_preference_id),
    INDEX idx_compras_pontos_payment (mercado_pago_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE transacoes_pontos
    ADD COLUMN IF NOT EXISTS compra_pontos_id INT NULL AFTER reserva_id;

CREATE INDEX idx_transacoes_compra_pontos ON transacoes_pontos (compra_pontos_id);

ALTER TABLE transacoes_pontos
    ADD CONSTRAINT fk_transacoes_compra_pontos
    FOREIGN KEY (compra_pontos_id) REFERENCES compras_pontos(id);
