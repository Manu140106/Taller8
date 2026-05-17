<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

$period = $_GET['period'] ?? 'all';
if (!in_array($period, ['week','month','all'])) $period = 'all';

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// ── Serie para la gráfica ────────────────────────────────────────────────────
if ($driver === 'sqlite') {
  if ($period === 'week') {
    $where  = "WHERE p.fecha >= datetime('now', '-7 days')";
    $group  = "strftime('%Y-%m-%d', p.fecha)";
    $label  = "strftime('%d/%m', p.fecha)";
  } elseif ($period === 'month') {
    $where  = "WHERE p.fecha >= datetime('now', '-1 month')";
    $group  = "strftime('%Y-%m-%d', p.fecha)";
    $label  = "strftime('%d/%m', p.fecha)";
  } else {
    $where  = "";
    $group  = "strftime('%Y-%m', p.fecha)";
    $label  = "strftime('%Y-%m', p.fecha)";
  }
} else {
  if ($period === 'week') {
    $where  = "WHERE p.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $group  = "DATE(p.fecha)";
    $label  = "DATE_FORMAT(p.fecha, '%d/%m')";
  } elseif ($period === 'month') {
    $where  = "WHERE p.fecha >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    $group  = "DATE(p.fecha)";
    $label  = "DATE_FORMAT(p.fecha, '%d/%m')";
  } else {
    $where  = "";
    $group  = "YEAR(p.fecha), MONTH(p.fecha)";
    $label  = "CONCAT(YEAR(p.fecha), '-', LPAD(MONTH(p.fecha),2,'0'))";
  }
}

$series = $pdo->query("
    SELECT MIN($label) AS etiqueta, ROUND(AVG(p.puntaje)) AS valor, COUNT(*) AS partidas
    FROM puntajes p $where
    GROUP BY $group
    ORDER BY MIN(p.fecha) ASC
)->fetchAll();


// ── Top 5 ────────────────────────────────────────────────────────────────────
$topUsuarios = $pdo->query("
    SELECT u.nombre, u.avatar, MAX(p.puntaje) AS mejor_puntaje, COUNT(p.idPuntajes) AS partidas
    FROM usuarios u
    INNER JOIN puntajes p ON p.usuario_id = u.usuario_id
    $where
    GROUP BY u.usuario_id, u.nombre, u.avatar
    ORDER BY mejor_puntaje DESC
    LIMIT 5
")->fetchAll();

// ── Stats generales ──────────────────────────────────────────────────────────
$statsRow = $pdo->query("
    SELECT COUNT(*) AS total_partidas,
           COALESCE(MAX(puntaje),0) AS mejor_global,
           COALESCE(ROUND(AVG(puntaje)),0) AS promedio_global,
           COUNT(DISTINCT usuario_id) AS jugadores
    FROM puntajes p $where
")->fetch();

// Log
$log->info("Reporte consultado | Período: $period");
$stmt = $pdo->prepare("INSERT INTO logs_db (usuario_id, accion, detalle) VALUES (NULL, 'REPORTE', ?)");
$stmt->execute(["Reporte | Período: $period"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TriviaScore — Reportes</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  :root { --bg:#0d0d1a; --card:#16162a; --border:#2a2a4a; --purple:#7c3aed; --cyan:#06b6d4; --yellow:#fbbf24; --green:#22c55e; --pink:#ec4899; --text:#f1f5f9; --muted:#94a3b8; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Nunito',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
  body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse 60% 40% at 20% 20%,rgba(124,58,237,.2) 0%,transparent 60%),radial-gradient(ellipse 50% 50% at 80% 80%,rgba(6,182,212,.15) 0%,transparent 60%); pointer-events:none; }
  nav { display:flex; align-items:center; justify-content:space-between; padding:1rem 2rem; background:rgba(13,13,26,.9); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); }
  .logo { font-family:'Fredoka One',cursive; font-size:1.5rem; background:linear-gradient(90deg,var(--purple),var(--cyan)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .nav-links { display:flex; gap:.5rem; }
  .nav-links a { text-decoration:none; color:var(--muted); font-size:.9rem; font-weight:600; padding:.4rem .9rem; border-radius:8px; transition:all .2s; }
  .nav-links a:hover,.nav-links a.active { background:var(--purple); color:#fff; }
  main { max-width:1100px; margin:0 auto; padding:2rem 1rem; }
  h1 { font-family:'Fredoka One',cursive; font-size:2rem; margin-bottom:.3rem; background:linear-gradient(90deg,#fff,var(--cyan)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .subtitle { color:var(--muted); margin-bottom:1.5rem; }
  .tabs { display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:1.5rem; }
  .tabs a { text-decoration:none; padding:.6rem 1.3rem; border-radius:30px; font-weight:700; font-size:.9rem; border:1px solid var(--border); color:var(--muted); transition:all .2s; }
  .tabs a:hover { border-color:var(--purple); color:var(--text); }
  .tabs a.active { background:linear-gradient(135deg,var(--purple),var(--cyan)); color:#fff; border-color:transparent; box-shadow:0 4px 16px rgba(124,58,237,.35); }
  .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:1rem; margin-bottom:1.5rem; }
  .stat-box { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:1.2rem; text-align:center; }
  .stat-box .n { font-family:'Fredoka One',cursive; font-size:2rem; }
  .stat-box .l { font-size:.8rem; color:var(--muted); margin-top:.2rem; }
  .stat-box.p .n{color:var(--yellow)} .stat-box.g .n{color:var(--green)} .stat-box.c .n{color:var(--cyan)} .stat-box.m .n{color:var(--pink)}
  .main-grid { display:grid; grid-template-columns:1fr 320px; gap:1.2rem; align-items:start; }
  @media(max-width:800px){.main-grid{grid-template-columns:1fr}}
  .chart-card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:1.5rem; }
  .chart-card h3 { font-family:'Fredoka One',cursive; font-size:1.2rem; margin-bottom:1rem; color:var(--cyan); }
  .leader-card { background:var(--card); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
  .leader-card h3 { font-family:'Fredoka One',cursive; font-size:1.2rem; padding:1.2rem 1.5rem; border-bottom:1px solid var(--border); color:var(--yellow); }
  .leader-item { display:flex; align-items:center; gap:.9rem; padding:.9rem 1.2rem; border-bottom:1px solid rgba(42,42,74,.5); transition:background .15s; }
  .leader-item:last-child{border:none} .leader-item:hover{background:rgba(124,58,237,.08)}
  .rank{font-family:'Fredoka One',cursive;font-size:1.3rem;min-width:28px;text-align:center}
  .rank-1{color:var(--yellow)} .rank-2{color:#94a3b8} .rank-3{color:#cd7f32}
  .leader-avatar{font-size:1.8rem}
  .leader-name{font-weight:700;font-size:.95rem}
  .leader-pts{font-size:.85rem;color:var(--muted)}
  .leader-score{font-family:'Fredoka One',cursive;font-size:1.2rem;color:var(--cyan);margin-left:auto}
  .empty{text-align:center;padding:3rem;color:var(--muted)}
</style>
</head>
<body>
<nav>
  <span class="logo">🏆 TriviaScore</span>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="history.php">Mi Puntaje</a>
    <a href="reports.php" class="active">Reportes</a>
    <a href="logs.php">Logs</a>
  </div>
</nav>
<main>
  <h1>📈 Reportes</h1>
  <p class="subtitle">Estadísticas agrupadas por período de tiempo</p>

  <div class="tabs">
    <a href="reports.php?period=week"  class="<?= $period==='week' ?'active':'' ?>">📅 Esta semana</a>
    <a href="reports.php?period=month" class="<?= $period==='month'?'active':'' ?>">🗓 Este mes</a>
    <a href="reports.php?period=all"   class="<?= $period==='all'  ?'active':'' ?>">🌍 Todo el tiempo</a>
  </div>

  <div class="stats-grid">
    <div class="stat-box p"><div class="n"><?= $statsRow['mejor_global'] ?></div><div class="l">🏆 Mejor puntaje</div></div>
    <div class="stat-box g"><div class="n"><?= $statsRow['promedio_global'] ?></div><div class="l">📊 Promedio</div></div>
    <div class="stat-box c"><div class="n"><?= $statsRow['total_partidas'] ?></div><div class="l">🎮 Partidas</div></div>
    <div class="stat-box m"><div class="n"><?= $statsRow['jugadores'] ?></div><div class="l">👥 Jugadores</div></div>
  </div>

  <div class="main-grid">
    <div class="chart-card">
      <h3>Puntaje promedio por período</h3>
      <?php if (count($series) > 0): ?>
        <canvas id="chartBar" height="120"></canvas>
        <script>
        new Chart(document.getElementById('chartBar'), {
          type: 'bar',
          data: {
            labels: <?= json_encode(array_column($series,'etiqueta')) ?>,
            datasets:[{ label:'Puntaje promedio', data:<?= json_encode(array_column($series,'valor')) ?>,
              backgroundColor:'rgba(124,58,237,.6)', borderColor:'#7c3aed', borderWidth:2, borderRadius:8 }]
          },
          options: { responsive:true, plugins:{legend:{display:false}},
            scales:{ x:{ticks:{color:'#94a3b8'},grid:{color:'#2a2a4a'}}, y:{ticks:{color:'#94a3b8'},grid:{color:'#2a2a4a'},beginAtZero:true} }
          }
        });
        </script>
      <?php else: ?>
        <div class="empty"><span style="font-size:2.5rem">📊</span><p>No hay datos para este período</p></div>
      <?php endif; ?>
    </div>

    <div class="leader-card">
      <h3>🏅 Top 5 jugadores</h3>
      <?php if (count($topUsuarios) > 0):
        $medalles = ['rank-1','rank-2','rank-3','',''];
        foreach ($topUsuarios as $i => $u): ?>
        <div class="leader-item">
          <span class="rank <?= $medalles[$i] ?? '' ?>"><?= $i+1 ?></span>
          <span class="leader-avatar"><?= $u['avatar'] ?></span>
          <div>
            <div class="leader-name"><?= htmlspecialchars($u['nombre']) ?></div>
            <div class="leader-pts"><?= $u['partidas'] ?> partida<?= $u['partidas']!=1?'s':'' ?></div>
          </div>
          <span class="leader-score"><?= $u['mejor_puntaje'] ?></span>
        </div>
      <?php endforeach; else: ?>
        <div class="empty"><p>Sin datos aún</p></div>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>