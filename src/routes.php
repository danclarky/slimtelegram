<?

use App\Controller\TelegramController;
use Slim\App;

return function (App $app) {
    $app->get('/get-webhook-info', TelegramController::class . ':getWebhookInfo');
    $app->get('/set-webhook', TelegramController::class . ':setWebhook');
    $app->get('/get-subscribers', TelegramController::class . ':getSubscribers');

    $app->post('/send-message-to-subscriber', TelegramController::class . ':sendMessageToSubscriber');
    $app->post('/bot-update', TelegramController::class . ':onTelegramUpdate');
    $app->post('/unsubscribe-users-from-chat', TelegramController::class . ':unsubscribeUsersFromChat');
};
