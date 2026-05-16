<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

function app_avatar_for_name(string $nombre): string
{
    $avatars = ['🎮', '⭐', '🔥', '🎯', '🚀', '🎲', '🏆', '💥', '🌟', '⚡'];
    $index = abs(crc32(strtolower(trim($nombre)))) % count($avatars);

    return $avatars[$index];
}

function app_current_user(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    global $pdo;

    $stmt = $pdo->prepare('SELECT usuario_id, nombre, avatar FROM usuarios WHERE usuario_id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    return $usuario ?: null;
}

function app_require_user(): array
{
    $usuario = app_current_user();
    if (!$usuario) {
        header('Location: index.php');
        exit;
    }

    return $usuario;
}

function app_find_or_create_user(string $nombre): array
{
    global $pdo, $log;

    $stmt = $pdo->prepare('SELECT usuario_id, nombre, avatar FROM usuarios WHERE nombre = ? LIMIT 1');
    $stmt->execute([$nombre]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        return $usuario;
    }

    $avatar = app_avatar_for_name($nombre);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, avatar) VALUES (?, ?)');
    $stmt->execute([$nombre, $avatar]);

    $usuario = [
        'usuario_id' => (int) $pdo->lastInsertId(),
        'nombre' => $nombre,
        'avatar' => $avatar,
    ];

    $log->info('Usuario creado | Nombre: ' . $nombre . ' | ID: ' . $usuario['usuario_id']);

    return $usuario;
}

function app_insert_log(?int $usuarioId, string $accion, string $detalle, string $nivel = 'INFO'): void
{
    global $pdo, $log;

    $stmt = $pdo->prepare('INSERT INTO logs_db (usuario_id, accion, detalle) VALUES (?, ?, ?)');
    $stmt->execute([$usuarioId, $accion, $detalle]);

    $mensaje = '[' . $nivel . '] ' . $detalle;
    if ($nivel === 'ERROR') {
        $log->error($mensaje);
        return;
    }

    if ($nivel === 'WARNING') {
        $log->warning($mensaje);
        return;
    }

    $log->info($mensaje);
}

function app_save_score(int $usuarioId, int $correctas, int $totalPreguntas): array
{
    global $pdo;

    $totalPreguntas = max(1, $totalPreguntas);
    $correctas = max(0, min($totalPreguntas, $correctas));
    $puntaje = (int) round(($correctas / $totalPreguntas) * 1000);

    $stmt = $pdo->prepare('INSERT INTO puntajes (usuario_id, puntaje, total_preguntas, correctas) VALUES (?, ?, ?, ?)');
    $stmt->execute([$usuarioId, $puntaje, $totalPreguntas, $correctas]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'puntaje' => $puntaje,
        'correctas' => $correctas,
        'total_preguntas' => $totalPreguntas,
    ];
}

function app_user_stats(int $usuarioId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COUNT(*) AS partidas, COALESCE(MAX(puntaje), 0) AS mejor, COALESCE(ROUND(AVG(puntaje)), 0) AS promedio FROM puntajes WHERE usuario_id = ?');
    $stmt->execute([$usuarioId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['partidas' => 0, 'mejor' => 0, 'promedio' => 0];
}

function app_user_history(int $usuarioId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT puntaje, correctas, total_preguntas, fecha FROM puntajes WHERE usuario_id = ? ORDER BY fecha ASC, idPuntajes ASC');
    $stmt->execute([$usuarioId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function app_last_score(int $usuarioId): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM puntajes WHERE usuario_id = ? ORDER BY fecha DESC, idPuntajes DESC LIMIT 1');
    $stmt->execute([$usuarioId]);
    $score = $stmt->fetch(PDO::FETCH_ASSOC);

    return $score ?: null;
}

function app_recent_users(int $limit = 5): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT u.usuario_id, u.nombre, u.avatar, COALESCE(MAX(p.puntaje), 0) AS mejor_puntaje
         FROM usuarios u
         LEFT JOIN puntajes p ON p.usuario_id = u.usuario_id
         GROUP BY u.usuario_id, u.nombre, u.avatar
         ORDER BY u.usuario_id DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function app_top_users(string $period = 'all', int $limit = 5): array
{
    global $pdo;

    $where = '';
    if ($period === 'week') {
        $where = 'WHERE p.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($period === 'month') {
        $where = 'WHERE p.fecha >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
    }

    $stmt = $pdo->query(
        'SELECT u.usuario_id, u.nombre, u.avatar, COALESCE(MAX(p.puntaje), 0) AS mejor_puntaje
         FROM usuarios u
         LEFT JOIN puntajes p ON p.usuario_id = u.usuario_id
         ' . $where . '
         GROUP BY u.usuario_id, u.nombre, u.avatar
         ORDER BY mejor_puntaje DESC, u.nombre ASC
         LIMIT ' . (int) $limit
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function app_report_series(string $period = 'all'): array
{
    global $pdo;

    $group = 'DATE(p.fecha)';
    $label = 'DATE_FORMAT(p.fecha, "%d %b")';
    $where = '';

    if ($period === 'week') {
        $where = 'WHERE p.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($period === 'month') {
        $where = 'WHERE p.fecha >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
    } else {
        $group = 'DATE_FORMAT(p.fecha, "%Y-%m")';
        $label = 'DATE_FORMAT(p.fecha, "%b %Y")';
    }

    $stmt = $pdo->query(
        'SELECT ' . $label . ' AS etiqueta, ROUND(AVG(p.puntaje)) AS valor
         FROM puntajes p
         ' . $where . '
         GROUP BY ' . $group . '
         ORDER BY MIN(p.fecha) ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function app_render_top(string $title, string $active = 'inicio', string $extraCss = ''): void
{
    $nav = [
        'inicio' => 'index.php',
        'historial' => 'history.php',
        'reportes' => 'reports.php',
        'logs' => 'logs.php',
    ];

    $activeLabels = [
        'inicio' => 'Inicio',
        'historial' => 'Mi Puntaje',
        'reportes' => 'Reportes',
        'logs' => 'Logs',
    ];

    $commonCss = <<<'CSS'
:root {
  --bg: #0d1020;
  --panel: rgba(24, 26, 52, 0.9);
  --panel-strong: #10122a;
  --border: rgba(111, 118, 184, 0.26);
  --purple: #8b5cf6;
  --violet: #a855f7;
  --cyan: #22d3ee;
  --blue: #60a5fa;
  --yellow: #facc15;
  --green: #22c55e;
  --red: #ef4444;
  --text: #eef2ff;
  --muted: #9ca3c7;
}

* { box-sizing: border-box; }
html, body { margin: 0; min-height: 100%; }
body {
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  color: var(--text);
  background:
    radial-gradient(circle at 50% 0%, rgba(168, 85, 247, 0.32), transparent 30%),
    radial-gradient(circle at 15% 70%, rgba(34, 211, 238, 0.18), transparent 28%),
    linear-gradient(180deg, #1a0f3f 0%, #12142a 52%, #0c1020 100%);
  min-height: 100vh;
}

body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
  background-size: 26px 26px;
  opacity: 0.12;
  pointer-events: none;
}

a { color: inherit; }
.topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.8rem;
  background: rgba(13, 15, 34, 0.86);
  backdrop-filter: blur(14px);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-weight: 800;
  font-size: 1.35rem;
  color: #bfa8ff;
}

.brand .icon {
  font-size: 1.7rem;
  color: var(--cyan);
}

.navlinks {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  flex-wrap: wrap;
}

.navlinks a {
  text-decoration: none;
  color: var(--muted);
  padding: 0.75rem 1.05rem;
  border-radius: 999px;
  transition: 0.2s ease;
  font-weight: 700;
}

.navlinks a.active,
.navlinks a:hover {
  background: linear-gradient(135deg, var(--purple), var(--cyan));
  color: #fff;
  box-shadow: 0 10px 24px rgba(124, 58, 237, 0.32);
}

.page-shell {
  width: min(1440px, calc(100% - 2rem));
  margin: 0 auto;
  padding: 2rem 0 3rem;
}

.section-title {
  font-size: clamp(2rem, 4vw, 3.4rem);
  line-height: 1.05;
  margin: 0 0 0.5rem;
  color: #b78cff;
  letter-spacing: -0.03em;
}

.section-subtitle {
  margin: 0;
  color: var(--muted);
  font-size: 1.05rem;
}

.panel {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 24px;
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.32);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  border: 0;
  text-decoration: none;
  cursor: pointer;
  padding: 0.95rem 1.4rem;
  border-radius: 16px;
  font-weight: 800;
  transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
}

.btn:hover { transform: translateY(-2px); }
.btn-primary { color: #fff; background: linear-gradient(135deg, var(--purple), var(--cyan)); box-shadow: 0 14px 26px rgba(124,58,237,.28); }
.btn-secondary { color: #e5ecff; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.09); }
.btn-soft { color: #c9d4ff; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); }
.muted { color: var(--muted); }

.grid {
  display: grid;
  gap: 1.2rem;
}

.stat-card {
  padding: 1.3rem 1.4rem;
  background: rgba(11, 15, 34, 0.72);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 20px;
}

.table-wrap { overflow: auto; border-radius: 20px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 1rem 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,.06); }
th { color: #9aa3cc; font-size: 0.92rem; }
tr:nth-child(even) td { background: rgba(255,255,255,0.02); }

@media (max-width: 860px) {
  .topbar { padding: 0.9rem 1rem; }
  .page-shell { width: min(100% - 1rem, 1440px); }
  .navlinks { gap: 0.45rem; }
  .navlinks a { padding: 0.6rem 0.8rem; }
}
CSS;

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' - TriviaScore</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">';
    echo '<style>' . $commonCss . $extraCss . '</style></head><body>';
    echo '<header class="topbar"><div class="brand"><span class="icon">🏆</span><span>TriviaScore</span></div><nav class="navlinks">';

    foreach ($nav as $key => $href) {
        $class = $key === $active ? 'active' : '';
        echo '<a class="' . $class . '" href="' . $href . '">' . $activeLabels[$key] . '</a>';
    }

    echo '</nav></header><main class="page-shell">';
}

function app_render_bottom(): void
{
    echo '</main></body></html>';
}

function app_level_class(string $nivel): string
{
    return match (strtoupper($nivel)) {
        'WARNING' => 'warning',
        'ERROR' => 'error',
        default => 'info',
    };
}
