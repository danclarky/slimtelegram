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
        'token' => '7387966375:AAFogARWt11WJMuoruEInPlPaV3XVBzv1sc',
        'webhook_url' => 'https://d8.trendup.pro/bot-update'
    ]
];
