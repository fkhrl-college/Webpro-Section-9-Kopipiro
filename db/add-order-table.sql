CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    cashier_id INT NOT NULL DEFAULT 1,

    total_price BIGINT UNSIGNED NOT NULL DEFAULT 0,

    checkout_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    served_at DATETIME NULL,

    is_serve BOOLEAN NOT NULL DEFAULT FALSE,

    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_cashier_id (cashier_id),
    INDEX idx_orders_is_serve (is_serve),
    INDEX idx_orders_checkout_at (checkout_at),

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_orders_cashier
        FOREIGN KEY (cashier_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);