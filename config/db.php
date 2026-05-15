<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// ── Conexión a la base de datos ──────────────────────────────────────────────
$host   = 'localhost';
$dbname = 'taller8';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
}

// ── Logger Monolog ───────────────────────────────────────────────────────────
$formato    = "%datetime% | %level_name% | %message%\n";
$formatter  = new LineFormatter($formato, "Y-m-d H:i:s");

$handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG);
$handler->setFormatter($formatter);

$log = new Logger('trivia_app');
$log->pushHandler($handler);