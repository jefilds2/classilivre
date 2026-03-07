CREATE TABLE IF NOT EXISTS purchase_request_closure_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    owner_user_id INT UNSIGNED NOT NULL,
    source ENUM('user','admin','system') NOT NULL DEFAULT 'user',
    reason_code VARCHAR(60) NOT NULL,
    reason_label VARCHAR(190) NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_purchase_request_closure_request FOREIGN KEY (request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_request_closure_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_purchase_request_closure_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_purchase_request_closure_request (request_id),
    INDEX idx_purchase_request_closure_owner (owner_user_id, created_at),
    INDEX idx_purchase_request_closure_reason (reason_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

