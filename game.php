<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php'); exit;
}

$nombre     = $_SESSION['nombre'];
$avatar     = $_SESSION['avatar'] ?? '🎮';
$usuario_id = (int)$_SESSION['usuario_id'];

// ── Banco de preguntas en español (se eligen 10 al azar) ────────────────────
$banco = [
    // Ciencia
    ['cat'=>'Ciencia','dif'=>'easy','preg'=>'¿Cuántos planetas tiene el Sistema Solar?','correcta'=>'8','ops'=>['6','7','9']],
    ['cat'=>'Ciencia','dif'=>'easy','preg'=>'¿Cuál es el elemento más abundante en el universo?','correcta'=>'Hidrógeno','ops'=>['Helio','Oxígeno','Carbono']],
    ['cat'=>'Ciencia','dif'=>'medium','preg'=>'¿A qué velocidad viaja la luz en el vacío (aprox.)?','correcta'=>'300.000 km/s','ops'=>['150.000 km/s','500.000 km/s','1.000.000 km/s']],
    ['cat'=>'Ciencia','dif'=>'medium','preg'=>'¿Cuál es el símbolo químico del oro?','correcta'=>'Au','ops'=>['Ag','Fe','Cu']],
    ['cat'=>'Ciencia','dif'=>'hard','preg'=>'¿Cuántos huesos tiene el cuerpo humano adulto?','correcta'=>'206','ops'=>['189','215','230']],
    ['cat'=>'Ciencia','dif'=>'hard','preg'=>'¿Qué partícula subatómica no tiene carga eléctrica?','correcta'=>'Neutrón','ops'=>['Protón','Electrón','Positrón']],
    ['cat'=>'Ciencia','dif'=>'medium','preg'=>'¿Cuál es el gas más abundante en la atmósfera terrestre?','correcta'=>'Nitrógeno','ops'=>['Oxígeno','Dióxido de carbono','Argón']],
    ['cat'=>'Ciencia','dif'=>'easy','preg'=>'¿Cuántos cromosomas tiene una célula humana normal?','correcta'=>'46','ops'=>['23','48','44']],

    // Historia
    ['cat'=>'Historia','dif'=>'easy','preg'=>'¿En qué año llegó Cristóbal Colón a América?','correcta'=>'1492','ops'=>['1488','1498','1502']],
    ['cat'=>'Historia','dif'=>'medium','preg'=>'¿Quién fue el primer presidente de los Estados Unidos?','correcta'=>'George Washington','ops'=>['Abraham Lincoln','Thomas Jefferson','Benjamin Franklin']],
    ['cat'=>'Historia','dif'=>'easy','preg'=>'¿En qué país se originó la Segunda Guerra Mundial?','correcta'=>'Alemania','ops'=>['Italia','Japón','Austria']],
    ['cat'=>'Historia','dif'=>'hard','preg'=>'¿En qué año cayó el Imperio Romano de Occidente?','correcta'=>'476 d.C.','ops'=>['395 d.C.','527 d.C.','410 d.C.']],
    ['cat'=>'Historia','dif'=>'medium','preg'=>'¿Quién construyó las pirámides de Giza?','correcta'=>'Los egipcios','ops'=>['Los romanos','Los griegos','Los persas']],
    ['cat'=>'Historia','dif'=>'medium','preg'=>'¿En qué año independizó Colombia?','correcta'=>'1810','ops'=>['1819','1821','1808']],
    ['cat'=>'Historia','dif'=>'hard','preg'=>'¿Cómo se llamaba el barco en el que viajó Cristóbal Colón en su primer viaje?','correcta'=>'La Santa María','ops'=>['La Victoria','El Galeón','La Niña']],

    // Geografía
    ['cat'=>'Geografía','dif'=>'easy','preg'=>'¿Cuál es el río más largo del mundo?','correcta'=>'El Nilo','ops'=>['El Amazonas','El Yangtsé','El Misisipi']],
    ['cat'=>'Geografía','dif'=>'easy','preg'=>'¿Cuál es el país más grande del mundo por superficie?','correcta'=>'Rusia','ops'=>['Canadá','China','Estados Unidos']],
    ['cat'=>'Geografía','dif'=>'medium','preg'=>'¿Cuál es la capital de Australia?','correcta'=>'Canberra','ops'=>['Sídney','Melbourne','Brisbane']],
    ['cat'=>'Geografía','dif'=>'medium','preg'=>'¿En qué continente se encuentra el desierto del Sahara?','correcta'=>'África','ops'=>['Asia','América del Sur','Australia']],
    ['cat'=>'Geografía','dif'=>'easy','preg'=>'¿Cuál es el océano más grande del mundo?','correcta'=>'Océano Pacífico','ops'=>['Océano Atlántico','Océano Índico','Océano Ártico']],
    ['cat'=>'Geografía','dif'=>'hard','preg'=>'¿Cuál es el país con más fronteras terrestres del mundo?','correcta'=>'China','ops'=>['Rusia','Brasil','Alemania']],
    ['cat'=>'Geografía','dif'=>'medium','preg'=>'¿Cuál es el volcán más alto del mundo?','correcta'=>'Ojos del Salado','ops'=>['Kilimanjaro','Monte Etna','Vesubio']],

    // Cultura general
    ['cat'=>'Cultura General','dif'=>'easy','preg'=>'¿Cuántos colores tiene el arcoíris?','correcta'=>'7','ops'=>['5','6','8']],
    ['cat'=>'Cultura General','dif'=>'easy','preg'=>'¿Cuántos lados tiene un hexágono?','correcta'=>'6','ops'=>['5','7','8']],
    ['cat'=>'Cultura General','dif'=>'medium','preg'=>'¿Quién escribió "Don Quijote de la Mancha"?','correcta'=>'Miguel de Cervantes','ops'=>['Lope de Vega','Francisco de Quevedo','Calderón de la Barca']],
    ['cat'=>'Cultura General','dif'=>'medium','preg'=>'¿Cuál es el instrumento musical con más cuerdas?','correcta'=>'Arpa','ops'=>['Piano','Guitarra','Violín']],
    ['cat'=>'Cultura General','dif'=>'easy','preg'=>'¿Cuántos jugadores hay en un equipo de fútbol?','correcta'=>'11','ops'=>['9','10','12']],
    ['cat'=>'Cultura General','dif'=>'hard','preg'=>'¿En qué año se fundó la ONU?','correcta'=>'1945','ops'=>['1919','1939','1950']],
    ['cat'=>'Cultura General','dif'=>'medium','preg'=>'¿Cuál es el deporte más practicado en el mundo?','correcta'=>'Fútbol','ops'=>['Baloncesto','Cricket','Tenis']],

    // Tecnología
    ['cat'=>'Tecnología','dif'=>'easy','preg'=>'¿Qué significa "www" en una dirección web?','correcta'=>'World Wide Web','ops'=>['World Web Wide','Wide World Web','Web World Wide']],
    ['cat'=>'Tecnología','dif'=>'medium','preg'=>'¿En qué año se lanzó el primer iPhone?','correcta'=>'2007','ops'=>['2005','2008','2010']],
    ['cat'=>'Tecnología','dif'=>'medium','preg'=>'¿Quién fundó Microsoft?','correcta'=>'Bill Gates y Paul Allen','ops'=>['Steve Jobs y Steve Wozniak','Mark Zuckerberg','Larry Page y Sergey Brin']],
    ['cat'=>'Tecnología','dif'=>'hard','preg'=>'¿Qué lenguaje de programación fue creado por Guido van Rossum?','correcta'=>'Python','ops'=>['Java','Ruby','Perl']],
    ['cat'=>'Tecnología','dif'=>'easy','preg'=>'¿Cuántos bits tiene un byte?','correcta'=>'8','ops'=>['4','16','32']],
    ['cat'=>'Tecnología','dif'=>'medium','preg'=>'¿Qué empresa creó el sistema operativo Android?','correcta'=>'Google','ops'=>['Apple','Samsung','Microsoft']],
    ['cat'=>'Tecnología','dif'=>'hard','preg'=>'¿En qué año se creó Internet (ARPANET)?','correcta'=>'1969','ops'=>['1975','1983','1991']],

    // Colombia
    ['cat'=>'Colombia','dif'=>'easy','preg'=>'¿Cuál es la capital de Colombia?','correcta'=>'Bogotá','ops'=>['Medellín','Cali','Barranquilla']],
    ['cat'=>'Colombia','dif'=>'medium','preg'=>'¿Cuál es el río más largo de Colombia?','correcta'=>'Río Magdalena','ops'=>['Río Cauca','Río Amazonas','Río Meta']],
    ['cat'=>'Colombia','dif'=>'medium','preg'=>'¿Cuántos departamentos tiene Colombia?','correcta'=>'32','ops'=>['28','30','36']],
    ['cat'=>'Colombia','dif'=>'easy','preg'=>'¿Cuál es la moneda de Colombia?','correcta'=>'Peso colombiano','ops'=>['Sol','Bolívar','Quetzal']],
];

// Mezclar y tomar 10
shuffle($banco);
$seleccionadas = array_slice($banco, 0, 10);

$preguntas = array_map(function($q) {
    $opciones = array_merge([$q['correcta']], $q['ops']);
    shuffle($opciones);
    return [
        'categoria'  => $q['cat'],
        'pregunta'   => $q['preg'],
        'correcta'   => $q['correcta'],
        'dificultad' => $q['dif'],
        'opciones'   => $opciones,
    ];
}, $seleccionadas);

$preguntasJson = json_encode($preguntas, JSON_UNESCAPED_UNICODE);
$log->info("Preguntas cargadas en español | Usuario: $nombre | Total: 10");
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
  body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse 60% 40% at 20% 20%,rgba(124,58,237,.2) 0%,transparent 60%),radial-gradient(ellipse 50% 50% at 80% 80%,rgba(6,182,212,.15) 0%,transparent 60%); pointer-events:none; }
  nav { display:flex; align-items:center; justify-content:space-between; padding:1rem 2rem; background:rgba(13,13,26,.9); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); }
  .logo { font-family:'Fredoka One',cursive; font-size:1.5rem; background:linear-gradient(90deg,var(--purple),var(--cyan)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .nav-links { display:flex; gap:.5rem; }
  .nav-links a { text-decoration:none; color:var(--muted); font-size:.9rem; font-weight:600; padding:.4rem .9rem; border-radius:8px; transition:all .2s; }
  .nav-links a:hover { background:var(--purple); color:#fff; }
  .player-badge { background:rgba(124,58,237,.2); border:1px solid var(--purple); border-radius:20px; padding:.3rem .9rem; font-size:.85rem; font-weight:700; color:var(--cyan); }
  main { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1rem; }
  #game { width:min(680px,95vw); animation:fadeIn .4s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
  .top-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; }
  .q-counter { font-family:'Fredoka One',cursive; font-size:1rem; color:var(--muted); }
  .score-badge { font-family:'Fredoka One',cursive; font-size:1.1rem; background:linear-gradient(135deg,var(--purple),var(--cyan)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .timer-wrap { margin-bottom:1rem; }
  .timer-bar { height:8px; background:var(--border); border-radius:10px; overflow:hidden; }
  .timer-fill { height:100%; width:100%; background:linear-gradient(90deg,var(--green),var(--yellow),var(--red)); border-radius:10px; transition:width 1s linear; }
  .timer-txt { text-align:right; font-size:.85rem; font-weight:700; color:var(--yellow); margin-top:.3rem; }
  .question-card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:2rem; margin-bottom:1.5rem; box-shadow:0 0 40px rgba(124,58,237,.12); }
  .tags { margin-bottom:1rem; display:flex; gap:.5rem; flex-wrap:wrap; }
  .category-tag { background:rgba(6,182,212,.15); color:var(--cyan); border:1px solid rgba(6,182,212,.3); border-radius:20px; padding:.2rem .8rem; font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
  .diff-tag { padding:.2rem .8rem; border-radius:20px; font-size:.8rem; font-weight:700; }
  .diff-easy{background:rgba(34,197,94,.15);color:var(--green)} .diff-medium{background:rgba(251,191,36,.15);color:var(--yellow)} .diff-hard{background:rgba(239,68,68,.15);color:var(--red)}
  .question-txt { font-size:clamp(1rem,3vw,1.3rem); font-weight:700; line-height:1.6; }
  .answers { display:grid; grid-template-columns:1fr 1fr; gap:.9rem; margin-bottom:1rem; }
  @media(max-width:500px){.answers{grid-template-columns:1fr}}
  .answer-btn { background:var(--card); border:2px solid var(--border); border-radius:14px; padding:1rem 1.2rem; color:var(--text); font-family:'Nunito',sans-serif; font-size:.95rem; font-weight:700; cursor:pointer; text-align:left; transition:all .2s; display:flex; align-items:center; gap:.7rem; }
  .answer-btn .letra { min-width:28px; height:28px; background:var(--border); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem; font-weight:800; flex-shrink:0; }
  .answer-btn:hover:not(:disabled) { border-color:var(--purple); background:rgba(124,58,237,.15); transform:translateY(-2px); }
  .answer-btn.correct { border-color:var(--green); background:rgba(34,197,94,.2); }
  .answer-btn.wrong   { border-color:var(--red);   background:rgba(239,68,68,.2); }
  .answer-btn:disabled { cursor:not-allowed; }
  .feedback { text-align:center; min-height:1.8rem; font-size:1.1rem; font-weight:800; }
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
  .stat.green .n{color:var(--green)} .stat.red .n{color:var(--red)}
  .btns { display:flex; gap:.8rem; flex-wrap:wrap; justify-content:center; }
  .btn { padding:.8rem 1.5rem; border-radius:12px; font-family:'Fredoka One',cursive; font-size:1.1rem; cursor:pointer; text-decoration:none; border:none; transition:transform .15s; }
  .btn:hover { transform:translateY(-2px); }
  .btn-primary { background:linear-gradient(135deg,var(--purple),var(--cyan)); color:#fff; box-shadow:0 4px 20px rgba(124,58,237,.4); }
  .btn-secondary { background:rgba(255,255,255,.08); color:var(--text); border:1px solid var(--border); }
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
      <div class="tags">
        <span class="category-tag" id="category">Categoría</span>
        <span class="diff-tag" id="diffTag">Fácil</span>
      </div>
      <p class="question-txt" id="questionTxt"></p>
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
        <div class="stat"><div class="n">10</div><div class="l">📋 Total</div></div>
      </div>
      <div class="btns">
        <button class="btn btn-primary" onclick="location.reload()">🔄 Jugar de nuevo</button>
        <a href="history.php" class="btn btn-secondary">📊 Ver mi historial</a>
      </div>
    </div>
  </div>

  <script>
  const USUARIO_ID = <?= $usuario_id ?>;
  const TOTAL_Q    = 10;
  const TIEMPO     = 30;
  const LETRAS     = ['A','B','C','D'];
  const preguntas  = <?= $preguntasJson ?>;

  let qIndex=0, puntaje=0, correctas=0;
  let timerID=null, tiempoLeft=TIEMPO, respondido=false;

  function diffLabel(d){return{easy:'Fácil',medium:'Media',hard:'Difícil'}[d]||d}
  function diffClass(d){return{easy:'diff-easy',medium:'diff-medium',hard:'diff-hard'}[d]||'diff-medium'}

  function mostrarPregunta(){
    if(qIndex>=preguntas.length){finJuego();return;}
    respondido=false;
    const q=preguntas[qIndex];
    document.getElementById('qCounter').textContent=`Pregunta ${qIndex+1} de ${TOTAL_Q}`;
    document.getElementById('category').textContent=q.categoria;
    document.getElementById('questionTxt').textContent=q.pregunta;
    document.getElementById('feedback').textContent='';
    const dt=document.getElementById('diffTag');
    dt.textContent=diffLabel(q.dificultad);
    dt.className='diff-tag '+diffClass(q.dificultad);
    const grid=document.getElementById('answersGrid');
    grid.innerHTML='';
    q.opciones.forEach((op,i)=>{
      const btn=document.createElement('button');
      btn.className='answer-btn';
      btn.innerHTML=`<span class="letra">${LETRAS[i]}</span><span>${op}</span>`;
      btn.onclick=()=>responder(btn,op,q.correcta);
      grid.appendChild(btn);
    });
    iniciarTimer();
  }

  function iniciarTimer(){
    tiempoLeft=TIEMPO; actualizarTimer(); clearInterval(timerID);
    timerID=setInterval(()=>{
      tiempoLeft--; actualizarTimer();
      if(tiempoLeft<=0){clearInterval(timerID);if(!respondido)tiempoAgotado();}
    },1000);
  }

  function actualizarTimer(){
    document.getElementById('timerFill').style.width=(tiempoLeft/TIEMPO*100)+'%';
    document.getElementById('timerTxt').textContent=tiempoLeft;
  }

  function tiempoAgotado(){
    respondido=true;
    document.getElementById('feedback').textContent='⏰ ¡Tiempo agotado!';
    document.getElementById('feedback').style.color='var(--yellow)';
    resaltarCorrecta();
    setTimeout(()=>{qIndex++;mostrarPregunta();},1800);
  }

  function responder(btn,opcion,correcta){
    if(respondido)return;
    respondido=true; clearInterval(timerID);
    document.querySelectorAll('.answer-btn').forEach(b=>b.disabled=true);
    if(opcion===correcta){
      btn.classList.add('correct');
      const bonus=Math.round((tiempoLeft/TIEMPO)*100);
      puntaje+=100+bonus; correctas++;
      document.getElementById('scoreDisplay').textContent=puntaje;
      document.getElementById('feedback').textContent=`✅ ¡Correcto! +${100+bonus} pts`;
      document.getElementById('feedback').style.color='var(--green)';
    }else{
      btn.classList.add('wrong'); resaltarCorrecta();
      document.getElementById('feedback').textContent=`❌ Era: ${correcta}`;
      document.getElementById('feedback').style.color='var(--red)';
    }
    setTimeout(()=>{qIndex++;mostrarPregunta();},1800);
  }

  function resaltarCorrecta(){
    const c=preguntas[qIndex].correcta;
    document.querySelectorAll('.answer-btn span:last-child').forEach(s=>{
      if(s.textContent===c)s.closest('.answer-btn').classList.add('correct');
    });
  }

  async function finJuego(){
    document.getElementById('game').style.display='none';
    document.getElementById('results').style.display='block';
    const incorrectas=TOTAL_Q-correctas;
    const estrellas=correctas>=8?'⭐⭐⭐':correctas>=5?'⭐⭐':'⭐';
    const emoji=correctas>=8?'🏆':correctas>=5?'🎉':'😅';
    const titulo=correctas>=8?'¡Excelente!':correctas>=5?'¡Bien hecho!':'¡Sigue intentando!';
    document.getElementById('resultEmoji').textContent=emoji;
    document.getElementById('resultTitle').textContent=titulo;
    document.getElementById('resultScore').textContent=puntaje;
    document.getElementById('resultStars').textContent=estrellas;
    document.getElementById('statCorrectas').textContent=correctas;
    document.getElementById('statWrong').textContent=incorrectas;
    try{
      const fd=new FormData();
      fd.append('usuario_id',USUARIO_ID);
      fd.append('puntaje',puntaje);
      fd.append('correctas',correctas);
      fd.append('total_preguntas',TOTAL_Q);
      await fetch('save_score.php',{method:'POST',body:fd});
    }catch(e){console.error(e);}
  }

  mostrarPregunta();
  </script>
</main>
</body>
</html>