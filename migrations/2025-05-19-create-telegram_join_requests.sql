CREATE TABLE IF NOT EXISTS telegram_join_requests
(
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `chat_id`    VARCHAR(255) NOT NULL,
    `user_id`    VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
