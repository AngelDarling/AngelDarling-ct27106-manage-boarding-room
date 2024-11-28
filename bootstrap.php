<?php

define('ROOTER', __DIR__ . DIRECTORY_SEPARATOR);

require_once ROOTER . 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOTER);
$dotenv->load();

try {
    $PDO = (new App\Models\PDOFactory())->create([
        'dbhost' => $_ENV['DB_HOST'],
        'dbname' => $_ENV['DB_NAME'],
        'dbuser' => $_ENV['DB_USER'],
        'dbpass' => $_ENV['DB_PASS'],
    ]);
} catch (Exception $ex) {
    echo 'Không thể kết nối đến MySQL,
		kiểm tra lại username/password đến MySQL.<br>';
    exit();
}
$appUrl = $_ENV['APP_URL'];

$AUTHGUARD = new App\SessionGuard();