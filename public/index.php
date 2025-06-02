<?
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use App\Service\DB;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/../');
$dotenv->load();

$container = new Container();
$config = require __DIR__ . '/../src/config.php';
$container->set('config', $config);
AppFactory::setContainer($container);

$app = AppFactory::create();

(require __DIR__ . '/../src/routes.php')($app);

$dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['database'];
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
DB::init($pdo);

$app->run();
