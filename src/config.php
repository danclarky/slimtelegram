<?

use Dotenv\Dotenv;

$dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'db' => [
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'database' => getenv('DB_NAME')
    ],
    'telegram' => [
        'token' => getenv('TELEGRAM_TOKEN'),
        'webhook_url' => getenv('TELEGRAM_WEBHOOK_URL'),
    ]
];
