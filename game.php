<?php
require_once __DIR__ . '/_app.php';

$usuario = app_require_user();

$questions = [
    ['question' => '¿Cuál es la capital de Colombia?', 'options' => ['Bogotá', 'Medellín', 'Cali', 'Barranquilla'], 'correct' => 'Bogotá'],
    ['question' => '¿Cuánto es 7 x 8?', 'options' => ['54', '56', '64', '58'], 'correct' => '56'],
    ['question' => '¿Qué lenguaje se usa en el navegador?', 'options' => ['Python', 'JavaScript', 'C++', 'SQL'], 'correct' => 'JavaScript'],
    ['question' => '¿Cuántos días tiene una semana?', 'options' => ['5', '6', '7', '8'], 'correct' => '7'],
    ['question' => '¿Qué color mezcla azul y amarillo?', 'options' => ['Morado', 'Verde', 'Naranja', 'Rojo'], 'correct' => 'Verde'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correctas = 0;

    foreach ($questions as $index => $question) {
        $answer = $_POST['q' . $index] ?? '';
        if ($answer === $question['correct']) {
            $correctas++;
        }
    }

    $resultado = app_save_score((int) $usuario['usuario_id'], $correctas, count($questions));
    app_insert_log((int) $usuario['usuario_id'], 'TRIVIA', 'Completó trivia con ' . $resultado['puntaje'] . ' puntos');

    $_SESSION['ultimo_score_id'] = $resultado['id'];
    $_SESSION['ultimo_score'] = $resultado;

    header('Location: results.php');
    exit;
}

$extraCss = <<<'CSS'
.hero-title {
  text-align: center;
  margin-bottom: 1.5rem;
}

.game-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(320px, 380px);
  gap: 1.4rem;
  align-items: start;
}

.game-card {
  padding: 1.5rem;
}

.question-card {
  padding: 1.25rem;
  background: rgba(11, 15, 34, 0.72);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 22px;
  margin-bottom: 1rem;
}

.progress-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 0.7rem;
}

.progress-bar {
  height: 12px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  overflow: hidden;
}

.progress-fill {
  width: 60%;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--purple), var(--cyan));
}

.answer-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  margin-top: 1rem;
}

.answer-option {
  position: relative;
}

.answer-option input {
  position: absolute;
  opacity: 0;
  inset: 0;
}

.answer-option label {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 92px;
  padding: 1rem;
  text-align: center;
  font-weight: 800;
  font-size: 1.02rem;
  border-radius: 18px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  cursor: pointer;
  transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}

.answer-option label:hover {
  transform: translateY(-2px);
  border-color: rgba(34, 211, 238, 0.5);
  background: rgba(34, 211, 238, 0.12);
}

.meta-card {
  padding: 1.5rem;
  display: grid;
  gap: 1rem;
}

.score-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  width: fit-content;
  padding: 0.55rem 0.85rem;
  border-radius: 999px;
  background: rgba(34,211,238,0.12);
  border: 1px solid rgba(34,211,238,0.2);
  color: #bffcff;
  font-weight: 800;
}

.player-box {
  padding: 1rem 1.1rem;
  border-radius: 18px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
}

@media (max-width: 980px) {
  .game-layout { grid-template-columns: 1fr; }
}
CSS;

app_render_top('Juego', 'inicio', $extraCss);
?>

<section class="hero-title">
  <h1 class="section-title">Trivia rápida</h1>
  <p class="section-subtitle">Responde las preguntas y guarda tu puntaje en la base de datos.</p>
</section>

<div class="game-layout">
  <div class="panel game-card">
    <form method="POST">
      <?php foreach ($questions as $index => $question): ?>
        <article class="question-card">
          <div class="progress-row">
            <strong>Pregunta <?= $index + 1 ?> de <?= count($questions) ?></strong>
            <span class="muted">Selecciona una respuesta</span>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width: <?= (($index + 1) / count($questions)) * 100 ?>%;"></div></div>
          <h2 style="margin: 1rem 0 0; font-size: 1.6rem;"><?= htmlspecialchars($question['question']) ?></h2>
          <div class="answer-grid">
            <?php foreach ($question['options'] as $option): ?>
              <div class="answer-option">
                <input type="radio" id="q<?= $index ?>_<?= md5($option) ?>" name="q<?= $index ?>" value="<?= htmlspecialchars($option) ?>" required>
                <label for="q<?= $index ?>_<?= md5($option) ?>"><?= htmlspecialchars($option) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>

      <button class="btn btn-primary" type="submit" style="width: 100%; margin-top: 0.5rem;">Finalizar trivia</button>
    </form>
  </div>

  <aside class="panel meta-card">
    <div class="score-chip">👤 <?= htmlspecialchars($usuario['avatar'] ?? '🎮') ?> <?= htmlspecialchars($usuario['nombre']) ?></div>
    <div class="player-box">
      <div class="muted">Puntaje actual</div>
      <div style="font-size: 2.4rem; font-weight: 900; color: #b78cff;">0</div>
    </div>
    <div class="player-box">
      <div class="muted">Flujo de juego</div>
      <p style="margin: 0.55rem 0 0; line-height: 1.5; color: #dce3ff;">Al finalizar, el puntaje se inserta en <strong>puntajes</strong> y el evento queda registrado en <strong>logs_db</strong>.</p>
    </div>
    <a class="btn btn-secondary" href="history.php">Ver mi historial</a>
  </aside>
</div>

<?php app_render_bottom();
