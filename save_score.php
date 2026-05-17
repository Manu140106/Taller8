<?php
require_once __DIR__ . '/_app.php';

$usuario = app_require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$correctas = (int) ($_POST['correctas'] ?? 0);
$totalPreguntas = (int) ($_POST['total_preguntas'] ?? 10);

try {
    $resultado = app_save_score((int) $usuario['usuario_id'], $correctas, $totalPreguntas);

    $_SESSION['ultimo_score_id'] = $resultado['id'];

    app_insert_log(
        (int) $usuario['usuario_id'],
        'PUNTAJE',
        'Puntaje guardado: ' . $resultado['puntaje'] . ' (' . $resultado['correctas'] . '/' . $resultado['total_preguntas'] . ')'
    );

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