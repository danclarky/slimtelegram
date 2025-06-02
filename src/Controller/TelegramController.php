<?

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Telegram\Bot\Api as ApiBot;
use Telegram\Bot\Exceptions\TelegramSDKException;
use App\Service\HttpLogService as Logger;
use App\Service\DB;


require __DIR__ . '/../Helper/utils.php';

class TelegramController
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onTelegramUpdate(Request $request, Response $response)
    {

        $token = $this->container->get('config')['telegram']['token'];
        $apiUrl = "https://api.telegram.org/bot$token/";
        $telegram = new ApiBot($token);
        $body = $request->getBody()->getContents();
        $chatIdIsSubscribe = '-4803914328';
        $update = json_decode($body, true);
        $isSendMessage = false;

        if (isset($update['message'])) {
            $chat_id = $update['message']['chat']['id'];
            $text = $update['message']['text'];
            $isSendMessage = $update['message']['text'] ? true : false;
        }

        if (isset($update['channel_post'])) {
            $chat_id = $update['channel_post']['chat']['id'];
            $text = $update['channel_post']['text'];
            $isSendMessage = $update['channel_post']['text'] ? true : false;
        }

        if (isset($update['chat_join_request'])) {
            $request = $update['chat_join_request'];
            $userId = $request['from']['id'];
            $chatId = $request['chat']['id'];

            DB::query("
            INSERT INTO " . DB::_table_telegram_join_requests() . " (`chat_id`, `user_id`)
            VALUES (:chat_id, :user_id)",
                [
                    'chat_id' => $chatId,
                    'user_id' => $userId,
                ]);

            $url = $apiUrl . "getChatMember?chat_id=$chatIdIsSubscribe&user_id=$userId";
            $responseChatMembers = file_get_contents($url);
            $data = json_decode($responseChatMembers, true);
            $isSubscribe = false;

            if ($data['ok']) {
                $status = $data['result']['status'];

                if (in_array($status, ['member', 'creator', 'administrator'])) {
                    $isSubscribe = true;
                }
            }

            $request = [
                'url' => $url
            ];

            Logger::logTelegramRequest($request, $data);

            if ($isSubscribe) {
                $url = $apiUrl . "approveChatJoinRequest?chat_id=$chatId&user_id=$userId";
                file_get_contents($url);
                $request = [
                    'url' => $url
                ];
            } else {
                $url = $apiUrl . "declineChatJoinRequest?chat_id=$chatId&user_id=$userId";
                file_get_contents($url);
                $request = [
                    'url' => $url
                ];
            }
            Logger::logTelegramRequest($request, []);
        }

        if ($isSendMessage) {
            $message = [
                'chat_id' => $chat_id,
                'text' => 'your text: ' . $body,
            ];
            $telegram->sendMessage($message);
            Logger::logTelegramRequest($update, $message);
        }

        return $response->withStatus(200)->write('OK');
    }

    public function getSubscribers(Request $request, Response $response)
    {
        $params = $request->getQueryParams();
        $typeGroup = isset($params['type']) ? $params['type'] : null;
        $chatId = isset($params['chat_id']) ? $params['chat_id'] : null;
        Logger::logTask($params);
        $task_id = DB::lastInsertId();
        $result = [];

        if (empty($typeGroup) || empty($chatId)) {
            $result = [
                "status" => "error",
                "description" => "Не заполнены обязательные параметры"
            ];
        } else {
            $sessionPath = realpath(__DIR__ . '/session.madeline');
            $MadelineProto = new API($sessionPath);

            $MadelineProto->getSelf();
            $result = [
                'status' => 'success',
                'chat_id' => $chatId
            ];
            if ($typeGroup == 'group') {
                try {
                    $chatFull = $MadelineProto->getFullInfo($chatId);
                    if (isset($chatFull['full']['participants']['participants'])) {

                        foreach ($chatFull['full']['participants']['participants'] as $participant) {
                            $userId = $participant['user_id'];
                            $user = $MadelineProto->getFullInfo($userId);
                            $id = $user['id'];
                            $firstName = $user['User']['first_name'] ?? null;
                            $username = $user['User']['username'] ?? null;
                            $phone = $user['User']['phone'] ?? null;
                            $link = $user['username']
                                ? "tg://resolve?domain={$user['username']}"
                                : null;
                            $result['users'][] = [
                                'id' => $id,
                                'username' => $username,
                                'first_name' => $firstName,
                                'phone' => $phone,
                                'link' => $link
                            ];
                        }
                    } else {
                        echo "Не удалось получить участников\n";
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $result = [
                        "status" => "error",
                        "description" => $e->getMessage()
                    ];
                }
            } else if ($typeGroup == 'channel') {
                try {
                    $fullInfo = $MadelineProto->getFullInfo($chatId);
                    $channelIdForRequest = (int)substr((string)$chatId, 4);
                    $inputChannel = [
                        '_' => 'inputChannel',
                        'channel_id' => $channelIdForRequest,
                        'access_hash' => $fullInfo['Chat']['access_hash']
                    ];

                    $participants = $MadelineProto->channels->getParticipants([
                        'channel' => $inputChannel,
                        'filter' => ['_' => 'channelParticipantsRecent'],
                    ]);

                    foreach ($participants['users'] as $user) {
                        $id = $user['id'];
                        $username = $user['username'] ?? null;
                        $firstName = $user['first_name'] ?? null;
                        $phone = $user['phone'] ?? null;
                        $link = isset($user['username'])
                            ? "tg://resolve?domain={$user['username']}"
                            : null;
                        $result['users'][] = [
                            'id' => $id,
                            'username' => $username,
                            'first_name' => $firstName,
                            'phone' => $phone,
                            'link' => $link
                        ];
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $result = [
                        "status" => "error",
                        "description" => $e->getMessage()
                    ];
                }
            } else {
                $result = [
                    "status" => "error",
                    "description" => "Некорректно заполнены обязательные параметры"
                ];
            }
        }
        $response->getBody()->write(json_encode($result));
        Logger::updateTask($task_id, $result);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function unsubscribeUsersFromChat(Request $request, Response $response)
    {
        $token = $this->container->get('config')['telegram']['token'];
        $apiUrl = "https://api.telegram.org/bot$token/";

        $body = $request->getBody()->getContents();
        $body = json_decode($body, true);
        if ($body == null) {
            $responseArray[] = [
                "status" => "error",
                "description" => "Пустое тело запроса"
            ];
        } else {
            Logger::logTask($body);
            $task_id = DB::lastInsertId();

            $responseArray = [];
            foreach ($body as $chat) {
                $chatId = isset($chat['chat_id']) ? $chat['chat_id'] : null;
                $tgIds = isset($chat['tg_ids']) ? $chat['tg_ids'] : null;

                if (empty($chatId) || empty($tgIds)) {
                    $responseArray[] = [
                        "status" => "error",
                        "description" => "Не заполнены обязательные параметры"
                    ];
                } else {
                    foreach ($tgIds as $tgId) {
                        $params = [
                            'chat_id' => $chatId,
                            'user_id' => $tgId,
                        ];
                        // kick всегда происходит бан в чате
                        $url = $apiUrl . "kickChatMember";

                        $result = requestTelegram($url, $params);

                        Logger::logTelegramRequest([$url => $params], $result);
                        $responseArray[] = [
                            'method' => 'kickChatMember',
                            'chat_id' => $chatId,
                            'user_id' => $tgId,
                            'response' => $result
                        ];
                        // unban если кикнули, то надо разбанить
                        if ($result['ok']) {
                            $url = $apiUrl . "unbanChatMember";

                            $result = requestTelegram($url, $params);

                            Logger::logTelegramRequest([$url => $params], $result);
                            $responseArray[] = [
                                'method' => 'unbanChatMember',
                                'chat_id' => $chatId,
                                'user_id' => $tgId,
                                'response' => $result
                            ];
                        }
                    }
                }
            }
            Logger::updateTask($task_id, $responseArray);
        }
        $response->getBody()->write(json_encode($responseArray));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function sendMessageToSubscriber(Request $request, Response $response)
    {
        $token = $this->container->get('config')['telegram']['token'];
        $responseArray = [];
        $telegram = new ApiBot($token);
        $body = $request->getBody()->getContents();
        $body = json_decode($body, true);

        if ($body == null) {
            $responseArray[] = [
                "status" => "error",
                "description" => "Пустое тело запроса"
            ];
        } else {
            Logger::logTask($body);
            $task_id = DB::lastInsertId();
            $chatId = isset($body['chat_id']) ? $body['chat_id'] : null;
            $message = isset($body['message']) ? $body['message'] : null;

            if (empty($chatId) || empty($message)) {
                $responseArray[] = [
                    "status" => "error",
                    "description" => "Не заполнены обязательные параметры"
                ];
            } else {
                try {
                    $message = [
                        'chat_id' => $chatId,
                        'text' => $message,
                    ];
                    $telegram->sendMessage($message);
                    $responseArray = [
                        "status" => "success",
                        "response" => "OK"
                    ];
                } catch (TelegramSDKException $e) {
                    $responseArray = [
                        "status" => "error",
                        "description" => $e->getMessage()
                    ];
                }
            }
            Logger::updateTask($task_id, $responseArray);
        }
        $response->getBody()->write(json_encode($responseArray));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function setWebhook(Request $request, Response $response)
    {
        $token = $this->container->get('config')['telegram']['token'];
        $webhookUrl = $this->container->get('config')['telegram']['webhook_url'];

        $telegram = new Api($token);

        $responseApi = $telegram->setWebhook([
            'url' => $webhookUrl
        ]);

        $responseApi = $responseApi ? 'Webhook установлен успешно!' : 'Ошибка при установке вебхука.';

        $response->getBody()->write($responseApi);

        return $response;
    }

    public function getWebhookInfo(Request $request, Response $response)
    {
        $token = $this->container->get('config')['telegram']['token'];
        $url = "https://api.telegram.org/bot{$token}/getWebhookInfo";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseCurl = curl_exec($ch);
        curl_close($ch);

        $response->getBody()->write($responseCurl);

        return $response;
    }

}
