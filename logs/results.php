<?php
require_once __DIR__ . '/_app.php';

$usuario = app_require_user();
$scoreId = (int) ($_SESSION['ultimo_score_id'] ?? 0);

if ($scoreId > 0) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT p.*, u.nombre, u.avatar FROM puntajes p INNER JOIN usuarios u ON u.usuario_id = p.usuario_id WHERE p.idPuntajes = ? AND p.usuario_id = ? LIMIT 1');
    $stmt->execute([$scoreId, $usuario['usuario_id']]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $resultado = app_last_score((int) $usuario['usuario_id']);
}

if (!$resultado) {
    header('Location: game.php');
    exit;
}

$stats = app_user_stats((int) $usuario['usuario_id']);
$mejor = (int) $stats['mejor'];
$actual = (int) $resultado['puntaje'];
$precision = (int) round(((int) $resultado['correctas'] / max(1, (int) $resultado['total_preguntas'])) * 100);
$estrellas = $precision >= 90 ? 3 : ($precision >= 60 ? 2 : 1);

$extraCss = <<<'CSS'
.center-wrap {
  min-height: calc(100vh - 180px);
  display: grid;
  place-items: center;
}

.result-card {
  width: min(620px, 100%);
  padding: 2rem;
  text-align: center;
}

.result-score {
  font-size: clamp(3rem, 8vw, 5rem);
  font-weight: 900;
  line-height: 1;
  color: #b8c4ff;
  margin: 0.3rem 0;
}

.result-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.85rem;
  margin: 1.4rem 0;
}

.result-box {
  padding: 1rem;
  border-radius: 18px;
  background: rgba(11, 15, 34, 0.74);
  border: 1px solid rgba(255,255,255,0.06);
}

.action-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.9rem;
  margin-top: 1.3rem;
}

.stars { font-size: 2rem; letter-spacing: 0.2rem; color: var(--yellow); }

@media (max-width: 700px) {
  .result-grid, .action-row { grid-template-columns: 1fr; }
}
CSS;

app_render_top('Resultados', 'inicio', $extraCss);
?>

<div class="center-wrap">
  <section class="panel result-card">
    <div style="font-size: 3.2rem; color: #facc15;">🏆</div>
    <h1 class="section-title" style="margin-top: 0.5rem;">¡Trivia completada!</h1>
    <p class="section-subtitle">Gran trabajo, <?= htmlspecialchars($resultado['nombre'] ?? $usuario['nombre']) ?></p>

    <div class="result-score"><?= $actual ?></div>
    <div class="muted" style="margin-bottom: 0.8rem;">puntos totales</div>
    <div class="stars"><?= str_repeat('★', $estrellas) ?></div>

    <div class="result-grid">
      <div class="result-box"><div class="muted">Correctas</div><strong style="font-size: 1.7rem; color: #22c55e;"><?= (int) $resultado['correctas'] ?></strong></div>
      <div class="result-box"><div class="muted">Incorrectas</div><strong style="font-size: 1.7rem; color: #fb7185;"><?= max(0, (int) $resultado['total_preguntas'] - (int) $resultado['correctas']) ?></strong></div>
      <div class="result-box"><div class="muted">Precisión</div><strong style="font-size: 1.7rem; color: #22d3ee;"><?= $precision ?>%</strong></div>
    </div>

    <div class="result-box">
      <div class="muted">Comparación con tu mejor puntaje</div>
      <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
        <div><strong style="color: #22d3ee; font-size: 1.35rem;"><?= $actual ?></strong><div class="muted">Actual</div></div>
        <div class="muted" style="align-self: center;">vs</div>
        <div><strong style="color: #b78cff; font-size: 1.35rem;"><?= $mejor ?></strong><div class="muted">Mejor</div></div>
      </div>
    </div>

    <div class="action-row">
      <a class="btn btn-primary" href="game.php">Jugar de nuevo</a>
      <a class="btn btn-secondary" href="history.php">Ver mi historial</a>
    </div>
  </section>
</div>

<?php app_render_bottom();
