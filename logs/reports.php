<?php
require_once __DIR__ . '/_app.php';

$usuario = app_require_user();
$period = $_GET['period'] ?? 'all';
if (!in_array($period, ['week', 'month', 'all'], true)) {
    $period = 'all';
}

$series = app_report_series($period);
$topUsers = app_top_users($period, 5);

$width = 760;
$height = 360;
$padding = 28;
$maxValue = max([1000, ...array_map(fn ($row) => (int) $row['valor'], $series)]);
$barWidth = count($series) > 0 ? (($width - ($padding * 2)) / count($series)) * 0.65 : 0;

$extraCss = <<<'CSS'
.tabs { display: flex; gap: 0.8rem; flex-wrap: wrap; margin: 1.2rem 0 1.5rem; }
.tabs a { text-decoration: none; padding: 0.95rem 1.3rem; border-radius: 18px; background: rgba(255,255,255,0.06); color: #cbd5ff; font-weight: 800; }
.tabs a.active { background: linear-gradient(135deg, var(--purple), var(--cyan)); color: #fff; }
.reports-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(300px, 360px); gap: 1.2rem; align-items: start; }
.report-card { padding: 1.4rem; }
.leader-item { display: flex; align-items: center; gap: 0.85rem; padding: 0.9rem 1rem; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 18px; margin-bottom: 0.8rem; }
.bar-label { font-size: 0.86rem; color: #98a1c9; }
@media (max-width: 980px) { .reports-grid { grid-template-columns: 1fr; } }
CSS;

app_render_top('Reportes', 'reportes', $extraCss);
?>

<h1 class="section-title">Reportes de rendimiento</h1>
<p class="section-subtitle">Consulta el comportamiento de puntajes por período.</p>

<div class="tabs">
  <a class="<?= $period === 'week' ? 'active' : '' ?>" href="reports.php?period=week">Esta semana</a>
  <a class="<?= $period === 'month' ? 'active' : '' ?>" href="reports.php?period=month">Este mes</a>
  <a class="<?= $period === 'all' ? 'active' : '' ?>" href="reports.php?period=all">Todo el tiempo</a>
</div>

<div class="reports-grid">
  <section class="panel report-card">
    <h2 style="margin-top: 0;">Puntajes promedio</h2>
    <div style="overflow-x: auto;">
      <svg viewBox="0 0 <?= $width ?> <?= $height ?>" xmlns="http://www.w3.org/2000/svg" style="width: 100%; min-width: 720px; display: block;">
        <?php for ($i = 0; $i <= 4; $i++): $y = $padding + ($i * (($height - ($padding * 2)) / 4)); ?>
          <line x1="<?= $padding ?>" y1="<?= $y ?>" x2="<?= $width - $padding ?>" y2="<?= $y ?>" stroke="rgba(255,255,255,0.08)" stroke-dasharray="4 4" />
        <?php endfor; ?>
        <?php foreach ($series as $index => $row):
          $x = $padding + ($index * (($width - ($padding * 2)) / max(1, count($series))));
          $value = (int) $row['valor'];
          $barHeight = max(18, (($value / $maxValue) * ($height - ($padding * 2))));
          $y = $height - $padding - $barHeight;
        ?>
          <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barWidth ?>" height="<?= $barHeight ?>" rx="14" fill="url(#barGradient)" />
          <text x="<?= $x + ($barWidth / 2) ?>" y="<?= $y - 10 ?>" text-anchor="middle" fill="#e9edff" font-size="14" font-weight="700"><?= (int) $value ?></text>
          <text x="<?= $x + ($barWidth / 2) ?>" y="<?= $height - 8 ?>" text-anchor="middle" fill="#98a1c9" font-size="13"><?= htmlspecialchars($row['etiqueta']) ?></text>
        <?php endforeach; ?>
        <defs>
          <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#a855f7" />
            <stop offset="100%" stop-color="#22d3ee" />
          </linearGradient>
        </defs>
      </svg>
    </div>
  </section>

  <aside class="panel report-card">
    <h2 style="margin-top: 0;">Clasificación Top 5</h2>
    <?php foreach ($topUsers as $position => $row): ?>
      <div class="leader-item">
        <div style="font-size: 1.5rem; width: 2rem; text-align: center;"><?= $position + 1 ?></div>
        <div style="font-size: 1.8rem;"><?= htmlspecialchars($row['avatar'] ?? '🎮') ?></div>
        <div style="flex: 1;">
          <div style="font-weight: 800;"><?= htmlspecialchars($row['nombre']) ?></div>
          <div class="muted"><?= (int) $row['mejor_puntaje'] ?> pts</div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$topUsers): ?>
      <p class="muted">Todavía no hay puntajes para clasificar.</p>
    <?php endif; ?>
  </aside>
</div>

<?php app_render_bottom();
