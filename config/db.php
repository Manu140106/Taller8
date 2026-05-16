<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// ── Conexión a la base de datos ──────────────────────────────────────────────
$host   = getenv('DB_HOST') ?: '127.0.0.1';
$port   = getenv('DB_PORT') ?: '3307';
$dbname = getenv('DB_NAME') ?: 'taller8';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Intentar fallback a SQLite para pruebas locales
    try {
        $sqliteFile = __DIR__ . '/../data/taller8.sqlite';
        if (!is_dir(dirname($sqliteFile))) {
            mkdir(dirname($sqliteFile), 0777, true);
        }
        $pdo = new PDO('sqlite:' . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Crear tablas si no existen (adaptadas de esquema MySQL)
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            usuario_id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            avatar TEXT DEFAULT '🎮',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS puntajes (
            idPuntajes INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            puntaje INTEGER NOT NULL,
            total_preguntas INTEGER DEFAULT 10,
            correctas INTEGER NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS logs_db (
            idLogs_db INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            accion TEXT NOT NULL,
            detalle TEXT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
        )");

    } catch (PDOException $ex) {
        die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage(), 'sqlite_error' => $ex->getMessage()]));
    }
}

// ── Logger Monolog ───────────────────────────────────────────────────────────
$formato    = "%datetime% | %level_name% | %message%\n";
$formatter  = new LineFormatter($formato, "Y-m-d H:i:s");

$handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG);
$handler->setFormatter($formatter);

$log = new Logger('trivia_app');
$log->pushHandler($handler);