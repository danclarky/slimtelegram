<?php
require __DIR__ . '/../../vendor/autoload.php';

use danog\MadelineProto\API;

$MadelineProto = new API(__DIR__ . '/session.madeline');

try {
    $me = $MadelineProto->getSelf();
    echo "✅ Уже авторизован как @" . $me['username'] . "\n";
} catch (\danog\MadelineProto\Exception | \danog\MadelineProto\RPCErrorException $e) {
    echo "🚀 Запуск авторизации...\n";
    $MadelineProto->start();
}
