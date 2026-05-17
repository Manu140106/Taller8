<?php
require_once __DIR__ . '/_app.php';

$usuario = app_current_user();
// Si no hay sesión activa, aceptar usuario_id enviado por POST como respaldo
if (!$usuario) {
    $postedId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
    if ($postedId) {
        $usuario = ['usuario_id' => $postedId];
    }
}
// Si aún no hay usuario, responder error (no autorizado)
if (!$usuario) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'No autorizado: falta sesión o usuario_id']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$correctas = (int) ($_POST['correctas'] ?? 0);
$totalPreguntas = (int) ($_POST['total_preguntas'] ?? 10);
$puntajeProvided = isset($_POST['puntaje']) ? (int) $_POST['puntaje'] : null;

try {
    $resultado = app_save_score((int) $usuario['usuario_id'], $correctas, $totalPreguntas, $puntajeProvided);

    // Si la sesión está activa, actualizar último id en sesión
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['ultimo_score_id'] = $resultado['id'];
    }

    app_insert_log(
        (int) $usuario['usuario_id'],
        'PUNTAJE',
        'Puntaje guardado: ' . $resultado['puntaje'] . ' (' . $resultado['correctas'] . '/' . $resultado['total_preguntas'] . ')'
    );

    // Log de depuración en archivo para ayudar diagnóstico si el cliente no ve cambios
    @file_put_contents(__DIR__ . '/logs/debug_save.txt', date('c') . " | user_id=" . (int)$usuario['usuario_id'] . " | puntaje=" . $resultado['puntaje'] . " | correctas=" . $resultado['correctas'] . " | total=" . $resultado['total_preguntas'] . "\n", FILE_APPEND);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'score_id' => $resultado['id'],
        'puntaje' => $resultado['puntaje'],
        'correctas' => $resultado['correctas'],
        'total_preguntas' => $resultado['total_preguntas'],
    ]);
} catch (Throwable $e) {
    app_insert_log((int) $usuario['usuario_id'], 'ERROR', 'Error guardando puntaje: ' . $e->getMessage(), 'ERROR');

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}