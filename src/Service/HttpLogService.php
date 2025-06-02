<?

namespace App\Service;

use App\Service\DB;

class HttpLogService
{
    public static function logTask(array $request): void
    {
        $table = DB::_table_telegram_tasks();

        DB::query("
            INSERT INTO `{$table}` (`request`)
            VALUES (:request)",
            [
                'request' => json_encode($request, JSON_UNESCAPED_UNICODE),
            ]);
    }

    public static function updateTask(int $id, array $response): void
    {
        $table = DB::_table_telegram_tasks();

        DB::query("
                UPDATE `{$table}`
                SET `response` = :response
                WHERE `id` = :id",
            [
                'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'id' => $id
            ]);
    }

    public static function logTelegramRequest(array $request, array $response): void
    {
        $table = DB::_table_telegram_logs();

        DB::query("
            INSERT INTO `{$table}` (`request`, `response`)
            VALUES (:request, :response)",
            [
                'request' => json_encode($request, JSON_UNESCAPED_UNICODE),
                'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
            ]);
    }
}
