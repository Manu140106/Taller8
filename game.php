<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php'); exit;
}
$nombre     = $_SESSION['nombre'];
$avatar     = $_SESSION['avatar'] ?? '🎮';
$usuario_id = (int)$_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TriviaScore — Jugando</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0d0d1a; --card:#16162a; --border:#2a2a4a; --purple:#7c3aed; --cyan:#06b6d4; --green:#22c55e; --red:#ef4444; --yellow:#fbbf24; --text:#f1f5f9; --muted:#94a3b8; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Nunito',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; flex-direction:column; }
  body::before {
    content:''; position:fixed; inset:0;
    background: radial-gradient(ellipse 60% 40% at 20% 20%,rgba(124,58,237,.2) 0%,transparent 60%),
                radial-gradient(ellipse 50% 50% at 80% 80%,rgba(6,182,212,.15) 0%,transparent 60%);
    pointer-events:none;
  }
  nav {
    display:flex; align-items:center; justify-content:space-between;
    padding:1rem 2rem; background:rgba(13,13,26,.9);
    backdrop-filter:blur(12px); border-bottom:1px solid var(--border);
  }
  .logo { font-family:'Fredoka One',cursive; font-size:1.5rem;
    background:linear-gradient(90deg,var(--purple),var(--cyan));
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .nav-links { display:flex; gap:.5rem; }
  .nav-links a { text-decoration:none; color:var(--muted); font-size:.9rem; font-weight:600; padding:.4rem .9rem; border-radius:8px; transition:all .2s; }
  .nav-links a:hover { background:var(--purple); color:#fff; }
  .player-badge { background:rgba(124,58,237,.2); border:1px solid var(--purple); border-radius:20px; padding:.3rem .9rem; font-size:.85rem; font-weight:700; color:var(--cyan); }

  main { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1rem; }

  /* Loading */
  #loading { text-align:center; }
  .spinner { width:60px; height:60px; border:4px solid var(--border); border-top-color:var(--purple); border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 1.5rem; }
  @keyframes spin { to{transform:rotate(360deg)} }
  #loading p { color:var(--muted); font-size:1.1rem; }

  /* Game */
  #game { display:none; width:min(680px,95vw); animation:fadeIn .4s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }

  .top-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; }
  .q-counter { font-family:'Fredoka One',cursive; font-size:1rem; color:var(--muted); }
  .score-badge { font-family:'Fredoka One',cursive; font-size:1.1rem;
    background:linear-gradient(135deg,var(--purple),var(--cyan));
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

  .timer-wrap { margin-bottom:1rem; }
  .timer-bar { height:8px; background:var(--border); border-radius:10px; overflow:hidden; }
  .timer-fill { height:100%; width:100%; background:linear-gradient(90deg,var(--green),var(--yellow),var(--red)); border-radius:10px; transition:width 1s linear; }
  .timer-txt { text-align:right; font-size:.85rem; font-weight:700; color:var(--yellow); margin-top:.3rem; }

  .question-card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:2rem; margin-bottom:1.5rem; box-shadow:0 0 40px rgba(124,58,237,.12); }
  .category-tag { display:inline-block; background:rgba(6,182,212,.15); color:var(--cyan); border:1px solid rgba(6,182,212,.3); border-radius:20px; padding:.2rem .8rem; font-size:.8rem; font-weight:700; margin-bottom:1rem; text-transform:uppercase; letter-spacing:.05em; }
  .question-txt { font-size:clamp(1rem,3vw,1.3rem); font-weight:700; line-height:1.5; }

  .answers { display:grid; grid-template-columns:1fr 1fr; gap:.9rem; margin-bottom:1rem; }
  @media(max-width:500px) { .answers{grid-template-columns:1fr} }

  .answer-btn {
    background:var(--card); border:2px solid var(--border); border-radius:14px;
    padding:1rem 1.2rem; color:var(--text); font-family:'Nunito',sans-serif;
    font-size:.95rem; font-weight:700; cursor:pointer; text-align:left;
    transition:all .2s; display:flex; align-items:center; gap:.7rem;
  }
  .answer-btn .letra { min-width:28px; height:28px; background:var(--border); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem; font-weight:800; }
  .answer-btn:hover:not(:disabled) { border-color:var(--purple); background:rgba(124,58,237,.15); transform:translateY(-2px); }
  .answer-btn.correct { border-color:var(--green); background:rgba(34,197,94,.2); }
  .answer-btn.wrong   { border-color:var(--red);   background:rgba(239,68,68,.2); }
  .answer-btn:disabled { cursor:not-allowed; opacity:.7; }

  .feedback { text-align:center; min-height:1.8rem; font-size:1.1rem; font-weight:800; animation:pop .3s ease; }
  @keyframes pop { from{transform:scale(.8)} to{transform:scale(1)} }

  /* Results */
  #results { display:none; width:min(520px,95vw); text-align:center; animation:fadeIn .5s ease; }
  .result-card { background:var(--card); border:1px solid var(--border); border-radius:24px; padding:2.5rem 2rem; box-shadow:0 0 60px rgba(124,58,237,.2); }
  .result-emoji { font-size:4rem; display:block; margin-bottom:1rem; }
  .result-title { font-family:'Fredoka One',cursive; font-size:2rem; margin-bottom:.5rem; }
  .result-score { font-family:'Fredoka One',cursive; font-size:4rem; background:linear-gradient(135deg,var(--yellow),#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin:.5rem 0; }
  .result-detail { color:var(--muted); margin-bottom:1rem; }
  .stars { font-size:2rem; margin-bottom:1.5rem; }
  .result-stats { display:flex; gap:1rem; justify-content:center; margin-bottom:2rem; }
  .stat { background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:14px; padding:.8rem 1.2rem; flex:1; }
  .stat .n { font-family:'Fredoka One',cursive; font-size:1.8rem; }
  .stat .l { font-size:.8rem; color:var(--muted); }
  .stat.green .n { color:var(--green); }
  .stat.red   .n { color:var(--red); }
  .btns { display:flex; gap:.8rem; flex-wrap:wrap; justify-content:center; }
  .btn { padding:.8rem 1.5rem; border-radius:12px; font-family:'Fredoka One',cursive; font-size:1.1rem; cursor:pointer; text-decoration:none; border:none; transition:transform .15s; }
  .btn:hover { transform:translateY(-2px); }
  .btn-primary { background:linear-gradient(135deg,var(--purple),var(--cyan)); color:#fff; box-shadow:0 4px 20px rgba(124,58,237,.4); }
  .btn-secondary { background:rgba(255,255,255,.08); color:var(--text); border:1px solid var(--border); }

  #error-box { display:none; text-align:center; background:rgba(239,68,68,.1); border:1px solid var(--red); border-radius:16px; padding:2rem; width:min(400px,90vw); }
</style>
</head>
<body>
<nav>
  <span class="logo">🏆 TriviaScore</span>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="reports.php">Reportes</a>
    <a href="logs.php">Logs</a>
  </div>
  <span class="player-badge"><?= $avatar ?> <?= htmlspecialchars($nombre) ?></span>
</nav>

<main>
  <div id="loading">
    <div class="spinner"></div>
    <p>Cargando preguntas desde Open Trivia DB...</p>
  </div>

  <div id="error-box">
    <p style="font-size:2rem;margin-bottom:1rem">😵</p>
    <p style="font-weight:700;margin-bottom:.5rem">No se pudo cargar la trivia</p>
    <p style="color:var(--muted);margin-bottom:1.5rem">Verifica tu conexión a internet</p>
    <button class="btn btn-primary" onclick="location.reload()">🔄 Reintentar</button>
  </div>

  <div id="game">
    <div class="top-bar">
      <span class="q-counter" id="qCounter">Pregunta 1 de 10</span>
      <span class="score-badge">⭐ Puntaje: <span id="scoreDisplay">0</span></span>
    </div>
    <div class="timer-wrap">
      <div class="timer-bar"><div class="timer-fill" id="timerFill"></div></div>
      <div class="timer-txt"><span id="timerTxt">30</span>s</div>
    </div>
    <div class="question-card">
      <span class="category-tag" id="category">Categoría</span>
      <p class="question-txt" id="questionTxt">Cargando...</p>
    </div>
    <div class="answers" id="answersGrid"></div>
    <div class="feedback" id="feedback"></div>
  </div>

  <div id="results">
    <div class="result-card">
      <span class="result-emoji" id="resultEmoji">🏆</span>
      <div class="result-title" id="resultTitle">¡Bien hecho!</div>
      <div class="result-score" id="resultScore">0</div>
      <div class="result-detail">puntos</div>
      <div class="stars" id="resultStars">⭐⭐⭐</div>
      <div class="result-stats">
        <div class="stat green"><div class="n" id="statCorrectas">0</div><div class="l">✅ Correctas</div></div>
        <div class="stat red"><div class="n" id="statWrong">0</div><div class="l">❌ Incorrectas</div></div>
        <div class="stat"><div class="n" id="statTotal">10</div><div class="l">📋 Total</div></div>
      </div>
      <div class="btns">
        <button class="btn btn-primary" onclick="location.reload()">🔄 Jugar de nuevo</button>
        <a href="history.php" class="btn btn-secondary">📊 Ver mi historial</a>
      </div>
    </div>
  </div>
</main>

<script>
const USUARIO_ID = <?= $usuario_id ?>;
const TOTAL_Q    = 10;
const TIEMPO     = 30;
const LETRAS     = ['A','B','C','D'];

let preguntas = [], qIndex = 0, puntaje = 0, correctas = 0;
let timerID = null, tiempoLeft = TIEMPO, respondido = false;

function decode(str) {
  const t = document.createElement('textarea');
  t.innerHTML = str; return t.value;
}
function shuffle(arr) {
  for (let i = arr.length-1; i>0; i--) {
    const j = Math.floor(Math.random()*(i+1));
    [arr[i],arr[j]] = [arr[j],arr[i]];
  }
  return arr;
}

async function cargarPreguntas() {
  try {
    const res  = await fetch('https://opentdb.com/api.php?amount=10&type=multiple&encode=url3986');
    const data = await res.json();
    if (data.response_code !== 0) throw new Error('API error');
    preguntas = data.results.map(q => ({
      categoria: decode(decodeURIComponent(q.category)),
      pregunta:  decode(decodeURIComponent(q.question)),
      correcta:  decode(decodeURIComponent(q.correct_answer)),
      opciones:  shuffle([
        decode(decodeURIComponent(q.correct_answer)),
        ...q.incorrect_answers.map(a => decode(decodeURIComponent(a)))
      ])
    }));
    document.getElementById('loading').style.display = 'none';
    document.getElementById('game').style.display    = 'block';
    mostrarPregunta();
  } catch(e) {
    document.getElementById('loading').style.display   = 'none';
    document.getElementById('error-box').style.display = 'block';
  }
}

function mostrarPregunta() {
  if (qIndex >= preguntas.length) { finJuego(); return; }
  respondido = false;
  const q = preguntas[qIndex];
  document.getElementById('qCounter').textContent    = `Pregunta ${qIndex+1} de ${TOTAL_Q}`;
  document.getElementById('category').textContent    = q.categoria;
  document.getElementById('questionTxt').textContent = q.pregunta;
  document.getElementById('feedback').textContent    = '';

  const grid = document.getElementById('answersGrid');
  grid.innerHTML = '';
  q.opciones.forEach((op, i) => {
    const btn = document.createElement('button');
    btn.className = 'answer-btn';
    btn.innerHTML = `<span class="letra">${LETRAS[i]}</span>${op}`;
    btn.onclick = () => responder(btn, op, q.correcta);
    grid.appendChild(btn);
  });
  iniciarTimer();
}

function iniciarTimer() {
  tiempoLeft = TIEMPO;
  actualizarTimer();
  clearInterval(timerID);
  timerID = setInterval(() => {
    tiempoLeft--;
    actualizarTimer();
    if (tiempoLeft <= 0) { clearInterval(timerID); if (!respondido) tiempoAgotado(); }
  }, 1000);
}

function actualizarTimer() {
  document.getElementById('timerFill').style.width = (tiempoLeft/TIEMPO*100)+'%';
  document.getElementById('timerTxt').textContent  = tiempoLeft;
}

function tiempoAgotado() {
  respondido = true;
  document.getElementById('feedback').textContent = '⏰ ¡Tiempo agotado!';
  document.getElementById('feedback').style.color = 'var(--yellow)';
  resaltarCorrecta();
  setTimeout(() => { qIndex++; mostrarPregunta(); }, 1800);
}

function responder(btn, opcion, correcta) {
  if (respondido) return;
  respondido = true;
  clearInterval(timerID);
  document.querySelectorAll('.answer-btn').forEach(b => b.disabled = true);

  if (opcion === correcta) {
    btn.classList.add('correct');
    const bonus = Math.round((tiempoLeft/TIEMPO)*100);
    puntaje += 100 + bonus; correctas++;
    document.getElementById('scoreDisplay').textContent = puntaje;
    document.getElementById('feedback').textContent = `✅ ¡Correcto! +${100+bonus} pts`;
    document.getElementById('feedback').style.color = 'var(--green)';
  } else {
    btn.classList.add('wrong');
    resaltarCorrecta();
    document.getElementById('feedback').textContent = `❌ Era: ${correcta}`;
    document.getElementById('feedback').style.color = 'var(--red)';
  }
  setTimeout(() => { qIndex++; mostrarPregunta(); }, 1800);
}

function resaltarCorrecta() {
  const correcta = preguntas[qIndex].correcta;
  document.querySelectorAll('.answer-btn').forEach(b => {
    if (b.textContent.includes(correcta)) b.classList.add('correct');
  });
}

async function finJuego() {
  document.getElementById('game').style.display    = 'none';
  document.getElementById('results').style.display = 'block';

  const incorrectas = TOTAL_Q - correctas;
  const estrellas   = correctas >= 8 ? '⭐⭐⭐' : correctas >= 5 ? '⭐⭐' : '⭐';
  const emoji       = correctas >= 8 ? '🏆' : correctas >= 5 ? '🎉' : '😅';
  const titulo      = correctas >= 8 ? '¡Excelente!' : correctas >= 5 ? '¡Bien hecho!' : '¡Sigue intentando!';

  document.getElementById('resultEmoji').textContent   = emoji;
  document.getElementById('resultTitle').textContent   = titulo;
  document.getElementById('resultScore').textContent   = puntaje;
  document.getElementById('resultStars').textContent   = estrellas;
  document.getElementById('statCorrectas').textContent = correctas;
  document.getElementById('statWrong').textContent     = incorrectas;
  document.getElementById('statTotal').textContent     = TOTAL_Q;

  try {
    const fd = new FormData();
    fd.append('usuario_id', USUARIO_ID);
    fd.append('puntaje', puntaje);
    fd.append('correctas', correctas);
    fd.append('total_preguntas', TOTAL_Q);
    await fetch('save_score.php', { method:'POST', body:fd });
  } catch(e) { console.error('Error guardando puntaje', e); }
}

cargarPreguntas();
</script>
</body>
</html>