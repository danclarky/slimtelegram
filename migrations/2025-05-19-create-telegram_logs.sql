CREATE TABLE IF NOT EXISTS telegram_logs
(
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request`    TEXT NOT NULL,
    `response`   TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
