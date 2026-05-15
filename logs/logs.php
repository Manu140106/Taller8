<?php
require_once __DIR__ . '/_app.php';

$usuario = app_require_user();
$nivel = strtoupper($_GET['nivel'] ?? 'ALL');
$fecha = trim($_GET['fecha'] ?? '');

$validLevels = ['ALL', 'INFO', 'WARNING', 'ERROR'];
if (!in_array($nivel, $validLevels, true)) {
    $nivel = 'ALL';
}

global $pdo;
$conditions = [];
$params = [];

if ($nivel !== 'ALL') {
    $conditions[] = 'l.accion = ?';
    $params[] = $nivel;
}

if ($fecha !== '') {
    $conditions[] = 'DATE(l.fecha) = ?';
    $params[] = $fecha;
}

$sql = "SELECT l.fecha, l.accion, l.detalle, COALESCE(u.nombre, '[system]') AS usuario_nombre FROM logs_db l LEFT JOIN usuarios u ON u.usuario_id = l.usuario_id";
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY l.fecha DESC, l.idLogs_db DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$extraCss = <<<'CSS'
.filters { display: flex; gap: 0.8rem; flex-wrap: wrap; margin: 1.2rem 0 1.2rem; align-items: center; }
.filters a, .filters input { border-radius: 14px; padding: 0.85rem 1rem; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.05); color: #d7e0ff; }
.filters a { text-decoration: none; font-weight: 800; }
.filters a.active { background: linear-gradient(135deg, var(--purple), var(--cyan)); color: #fff; }
.log-panel { background: #050608; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 1.25rem; overflow: hidden; }
.log-row { display: grid; grid-template-columns: 170px 110px 160px 1fr; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid rgba(255,255,255,0.06); align-items: center; }
.log-row:last-child { border-bottom: 0; }
.tag { display: inline-flex; align-items: center; justify-content: center; min-width: 86px; padding: 0.45rem 0.8rem; border-radius: 999px; font-weight: 900; }
.tag.info { background: rgba(34,197,94,0.13); color: #22c55e; border: 1px solid rgba(34,197,94,0.22); }
.tag.warning { background: rgba(250,204,21,0.14); color: #facc15; border: 1px solid rgba(250,204,21,0.22); }
.tag.error { background: rgba(239,68,68,0.13); color: #ef4444; border: 1px solid rgba(239,68,68,0.22); }
@media (max-width: 980px) { .log-row { grid-template-columns: 1fr; gap: 0.35rem; } }
CSS;

app_render_top('Logs', 'logs', $extraCss);
?>

<h1 class="section-title">Logs del sistema</h1>
<p class="section-subtitle">Eventos de entrada, partidas y reportes registrados en la aplicación.</p>

<form class="filters" method="GET">
  <a class="<?= $nivel === 'ALL' ? 'active' : '' ?>" href="logs.php?nivel=ALL">ALL</a>
  <a class="<?= $nivel === 'INFO' ? 'active' : '' ?>" href="logs.php?nivel=INFO">INFO</a>
  <a class="<?= $nivel === 'WARNING' ? 'active' : '' ?>" href="logs.php?nivel=WARNING">WARNING</a>
  <a class="<?= $nivel === 'ERROR' ? 'active' : '' ?>" href="logs.php?nivel=ERROR">ERROR</a>
  <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
  <button class="btn btn-primary" type="submit">Filtrar</button>
</form>

<section class="log-panel">
  <?php foreach ($logs as $log): ?>
    <div class="log-row">
      <div class="muted"><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['fecha']))) ?></div>
      <div><span class="tag <?= app_level_class($log['accion']) ?>"><?= htmlspecialchars($log['accion']) ?></span></div>
      <div style="color: #22d3ee; font-weight: 800;"><?= htmlspecialchars($log['usuario_nombre']) ?></div>
      <div><?= htmlspecialchars($log['detalle']) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$logs): ?>
    <p class="muted">No se encontraron registros con los filtros actuales.</p>
  <?php endif; ?>
</section>

<?php app_render_bottom();
