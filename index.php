<?php
require_once __DIR__ . '/_app.php';

// Si ya hay usuario en sesión, ir al juego
if (isset($_SESSION['usuario_id'])) {
    header('Location: game.php');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre !== '') {
    $usuario = app_find_or_create_user($nombre);

    $_SESSION['usuario_id'] = (int) $usuario['usuario_id'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['avatar'] = $usuario['avatar'];

    app_insert_log((int) $usuario['usuario_id'], 'LOGIN', 'Inicio de sesión: ' . $usuario['nombre']);
        header('Location: game.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TriviaScore — ¿Quién está jugando?</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:      #0d0d1a;
    --card:    #16162a;
    --border:  #2a2a4a;
    --purple:  #7c3aed;
    --cyan:    #06b6d4;
    --pink:    #ec4899;
    --yellow:  #fbbf24;
    --text:    #f1f5f9;
    --muted:   #94a3b8;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Nunito', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  /* Fondo animado */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background:
      radial-gradient(ellipse 60% 40% at 20% 20%, rgba(124,58,237,.25) 0%, transparent 60%),
      radial-gradient(ellipse 50% 50% at 80% 80%, rgba(6,182,212,.2) 0%, transparent 60%),
      radial-gradient(ellipse 40% 40% at 50% 50%, rgba(236,72,153,.12) 0%, transparent 60%);
    animation: bgPulse 8s ease-in-out infinite alternate;
    pointer-events: none;
  }
  @keyframes bgPulse {
    from { opacity: .7; }
    to   { opacity: 1; }
  }

  nav {
    position: fixed; top: 0; left: 0; right: 0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 2rem;
    background: rgba(13,13,26,.8);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    z-index: 100;
  }
  .logo {
    font-family: 'Fredoka One', cursive;
    font-size: 1.6rem;
    background: linear-gradient(90deg, var(--purple), var(--cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .nav-links { display: flex; gap: .5rem; }
  .nav-links a {
    text-decoration: none;
    color: var(--muted);
    font-size: .9rem;
    font-weight: 600;
    padding: .4rem .9rem;
    border-radius: 8px;
    transition: all .2s;
  }
  .nav-links a:hover, .nav-links a.active {
    background: var(--purple);
    color: #fff;
  }

  .hero {
    text-align: center;
    padding: 2rem 1rem;
    animation: fadeUp .6s ease both;
  }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .emoji-big { font-size: 4rem; display: block; margin-bottom: 1rem; animation: bounce 2s infinite; }
  @keyframes bounce {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
  }

  h1 {
    font-family: 'Fredoka One', cursive;
    font-size: clamp(2rem, 6vw, 3.5rem);
    margin-bottom: .5rem;
    background: linear-gradient(90deg, #fff 0%, var(--cyan) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .subtitle {
    color: var(--muted);
    font-size: 1.1rem;
    margin-bottom: 2.5rem;
  }

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2.5rem 2rem;
    width: min(420px, 90vw);
    box-shadow: 0 0 60px rgba(124,58,237,.15);
    animation: fadeUp .6s ease .15s both;
  }

  .input-wrap {
    position: relative;
    margin-bottom: 1.5rem;
  }
  .input-wrap .icon {
    position: absolute; left: 1rem; top: 50%;
    transform: translateY(-50%);
    font-size: 1.3rem; pointer-events: none;
  }
  input[type="text"] {
    width: 100%;
    padding: .9rem 1rem .9rem 3rem;
    background: rgba(255,255,255,.05);
    border: 2px solid var(--border);
    border-radius: 12px;
    color: var(--text);
    font-family: 'Nunito', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  input[type="text"]::placeholder { color: var(--muted); font-weight: 400; }
  input[type="text"]:focus {
    border-color: var(--purple);
    box-shadow: 0 0 0 4px rgba(124,58,237,.2);
  }

  .btn-play {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, var(--purple), var(--cyan));
    border: none;
    border-radius: 12px;
    color: #fff;
    font-family: 'Fredoka One', cursive;
    font-size: 1.3rem;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 4px 24px rgba(124,58,237,.4);
    display: flex; align-items: center; justify-content: center; gap: .5rem;
  }
  .btn-play:hover  { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(124,58,237,.5); }
  .btn-play:active { transform: scale(.97); }

  .divider {
    display: flex; align-items: center; gap: 1rem;
    margin: 1.5rem 0;
    color: var(--muted); font-size: .85rem;
  }
  .divider::before, .divider::after {
    content: ''; flex: 1;
    height: 1px; background: var(--border);
  }

  .users-grid {
    display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center;
  }
  .user-pill {
    background: rgba(255,255,255,.05);
    border: 1px solid var(--border);
    border-radius: 30px;
    padding: .35rem .9rem;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    color: var(--muted);
  }
  .user-pill:hover {
    background: var(--purple);
    color: #fff;
    border-color: var(--purple);
    transform: scale(1.05);
  }

  .error {
    background: rgba(236,72,153,.15);
    border: 1px solid var(--pink);
    color: var(--pink);
    border-radius: 10px;
    padding: .7rem 1rem;
    font-size: .9rem;
    margin-bottom: 1rem;
    text-align: center;
  }
</style>
</head>
<body>

<nav>
  <span class="logo">🏆 TriviaScore</span>
  <div class="nav-links">
    <a href="index.php" class="active">Inicio</a>
    <a href="reports.php">Reportes</a>
    <a href="logs.php">Logs</a>
  </div>
</nav>

<div class="hero">
  <span class="emoji-big">🎮</span>
  <h1>¿Quién está jugando?</h1>
  <p class="subtitle">Escribe tu nombre para comenzar la trivia</p>

  <div class="card">
    <?php if (isset($error)): ?>
      <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="input-wrap">
        <span class="icon">✏️</span>
        <input type="text" name="nombre" placeholder="Tu nombre aquí..." maxlength="80" autofocus autocomplete="off" required>
      </div>
      <button type="submit" class="btn-play">▶ ¡Jugar ahora!</button>
    </form>

    <div class="divider">o elige un jugador reciente</div>

    <div class="users-grid">
      <?php
      $stmt = $pdo->query("SELECT nombre, avatar FROM usuarios ORDER BY usuario_id DESC LIMIT 8");
      while ($u = $stmt->fetch()):
      ?>
        <span class="user-pill" onclick="document.querySelector('input[name=nombre]').value='<?= htmlspecialchars($u['nombre']) ?>'">
          <?= $u['avatar'] ?> <?= htmlspecialchars($u['nombre']) ?>
        </span>
      <?php endwhile; ?>
    </div>
  </div>
</div>

</body>
</html>