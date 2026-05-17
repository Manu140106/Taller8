<?php
require __DIR__ . '/config/db.php';

echo "PDO driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
try {
    $stmt = $pdo->query("SELECT DATABASE() AS dbname, @@hostname AS host, @@port AS port, USER() AS user");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Connected DB: " . ($info['dbname'] ?? '(unknown)') . "\n";
    echo "Host: " . ($info['host'] ?? '(unknown)') . "\n";
    echo "Port: " . ($info['port'] ?? '(unknown)') . "\n";
    echo "DB User: " . ($info['user'] ?? '(unknown)') . "\n\n";

    $counts = $pdo->query("SELECT (SELECT COUNT(*) FROM usuarios) AS u, (SELECT COUNT(*) FROM puntajes) AS p, (SELECT COUNT(*) FROM logs_db) AS l")->fetch(PDO::FETCH_ASSOC);
    echo "Row counts - usuarios: " . ($counts['u'] ?? 0) . ", puntajes: " . ($counts['p'] ?? 0) . ", logs_db: " . ($counts['l'] ?? 0) . "\n\n";

    echo "Last 10 puntajes:\n";
    $rows = $pdo->query("SELECT idPuntajes, usuario_id, puntaje, correctas, total_preguntas, fecha FROM puntajes ORDER BY fecha DESC, idPuntajes DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "(no rows)\n";
    } else {
        foreach ($rows as $r) {
            echo implode(' | ', $r) . "\n";
        }
    }

    echo "\nLogs debug file:\n";
    $dbg = @file_get_contents(__DIR__ . '/logs/debug_save.txt');
    if ($dbg === false) echo "(no debug file)\n";
    else echo $dbg;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
