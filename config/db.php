<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// ── Conexión a MySQL ────────────────────────────────────────────────────────
$host   = getenv('DB_HOST') ?: '127.0.0.1';
$port   = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'taller8';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: 'manuela140106';

try {
    $serverPdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        usuario_id INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(80) NOT NULL,
        avatar VARCHAR(10) DEFAULT '🎮',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS puntajes (
        idPuntajes INT PRIMARY KEY AUTO_INCREMENT,
        usuario_id INT NOT NULL,
        puntaje INT NOT NULL,
        total_preguntas INT DEFAULT 10,
        correctas INT NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS logs_db (
        idLogs_db INT PRIMARY KEY AUTO_INCREMENT,
        usuario_id INT,
        accion VARCHAR(50) NOT NULL,
        detalle LONGTEXT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    die(json_encode([
        'error' => 'No se pudo conectar a MySQL. Revisa DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS.',
        'detalle' => $e->getMessage(),
    ]));
}

// ── Logger Monolog ───────────────────────────────────────────────────────────
$formato    = "%datetime% | %level_name% | %message%\n";
$formatter  = new LineFormatter($formato, "Y-m-d H:i:s");

$handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG);
$handler->setFormatter($formatter);

$log = new Logger('trivia_app');
$log->pushHandler($handler);
