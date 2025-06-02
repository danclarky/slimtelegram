<?

namespace App\Service;

use PDO;

class DB
{

    protected static $pdo;

    public static function init(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function query(string $query, array $params = []): void
    {
        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);
    }

    public static function lastInsertId(): int
    {
        return (int)self::$pdo->lastInsertId();
    }

    public static function getResults(string $query, array $params = []): array
    {
        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getOne(string $query, array $params = []): array
    {
        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function _table_telegram_tasks(): string
    {
        return 'telegram_tasks';
    }

    public static function _table_telegram_logs(): string
    {
        return 'telegram_logs';
    }

    public static function _table_telegram_join_requests(): string
    {
        return 'telegram_join_requests';
    }
}
