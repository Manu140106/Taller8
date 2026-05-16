<?php
require_once __DIR__ . '/_app.php';

$usuario = app_require_user();
$historial = app_user_history((int) $usuario['usuario_id']);
$stats = app_user_stats((int) $usuario['usuario_id']);

$points = [];
foreach ($historial as $row) {
    $points[] = (int) $row['puntaje'];
}

$maxPoint = max([1000, ...$points]);
$width = 760;
$height = 320;
$padding = 24;
$svgPoints = [];
$count = max(1, count($historial));

foreach ($historial as $index => $row) {
    $x = $count === 1 ? $width / 2 : $padding + ($index * (($width - ($padding * 2)) / ($count - 1)));
    $value = (int) $row['puntaje'];
    $y = $height - $padding - (($value / $maxPoint) * ($height - ($padding * 2)));
    $svgPoints[] = round($x, 2) . ',' . round($y, 2);
}

$extraCss = <<<'CSS'
.header-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 1.3rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.2rem;
}

.chart-card { padding: 1.4rem; margin-bottom: 1.3rem; }
.chart-wrap { overflow-x: auto; }
.chart-svg { width: 100%; min-width: 720px; display: block; }

@media (max-width: 900px) { .stats-grid { grid-template-columns: 1fr; } }
CSS;

app_render_top('Mi Puntaje', 'historial', $extraCss);
?>

<div class="header-row">
  <div style="display: flex; align-items: center; gap: 1rem;">
    <div style="font-size: 3rem;"><?= htmlspecialchars($usuario['avatar'] ?? '🎮') ?></div>
    <div>
      <h1 class="section-title" style="margin-bottom: 0.15rem;"><?= htmlspecialchars($usuario['nombre']) ?></h1>
      <p class="section-subtitle">Historial de puntajes</p>
    </div>
  </div>
  <a class="btn btn-secondary" href="index.php">Cambiar usuario</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="muted">Mejor puntaje</div><div style="font-size: 2rem; font-weight: 900; color: #22d3ee;"><?= (int) $stats['mejor'] ?></div></div>
  <div class="stat-card"><div class="muted">Partidas jugadas</div><div style="font-size: 2rem; font-weight: 900; color: #c084fc;"><?= (int) $stats['partidas'] ?></div></div>
  <div class="stat-card"><div class="muted">Promedio</div><div style="font-size: 2rem; font-weight: 900; color: #22c55e;"><?= (int) $stats['promedio'] ?></div></div>
</div>

<section class="panel chart-card">
  <h2 style="margin: 0 0 1rem;">Evolución del puntaje</h2>
  <div class="chart-wrap">
    <svg class="chart-svg" viewBox="0 0 <?= $width ?> <?= $height ?>" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="lineGradient" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#a855f7" />
          <stop offset="100%" stop-color="#22d3ee" />
        </linearGradient>
      </defs>
      <?php for ($i = 0; $i <= 4; $i++): $y = $padding + ($i * (($height - ($padding * 2)) / 4)); ?>
        <line x1="<?= $padding ?>" y1="<?= $y ?>" x2="<?= $width - $padding ?>" y2="<?= $y ?>" stroke="rgba(255,255,255,0.08)" stroke-dasharray="4 4" />
      <?php endfor; ?>
      <?php if (count($svgPoints) > 1): ?>
        <polyline fill="none" stroke="url(#lineGradient)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', $svgPoints) ?>" />
      <?php endif; ?>
      <?php foreach ($svgPoints as $point): [$x, $y] = array_map('floatval', explode(',', $point)); ?>
        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="6" fill="#22d3ee" />
      <?php endforeach; ?>
    </svg>
  </div>
</section>

<section class="panel" style="padding: 1.4rem;">
  <h2 style="margin: 0 0 1rem;">Historial de partidas</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Puntaje</th>
          <th>Correctas</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historial as $row): ?>
          <tr>
            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha']))) ?></td>
            <td style="color: #22d3ee; font-weight: 800;"><?= (int) $row['puntaje'] ?></td>
            <td><?= (int) $row['correctas'] ?></td>
            <td><?= (int) $row['total_preguntas'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$historial): ?>
          <tr><td colspan="4" class="muted">Todavía no hay partidas registradas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php app_render_bottom();
