<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
 
if (isset($_SESSION['usuario_id'])) {
    header('Location: game.php');
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre !== '') {
        $stmt = $pdo->prepare('SELECT usuario_id, nombre, avatar FROM usuarios WHERE nombre = ? LIMIT 1');
        $stmt->execute([$nombre]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if (!$usuario) {
            $avatares = ['🎮','⭐','🔥','🎯','🚀','🎲','🏆','💥','🌟','⚡'];
            $avatar   = $avatares[abs(crc32(strtolower($nombre))) % count($avatares)];
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, avatar) VALUES (?, ?)');
            $stmt->execute([$nombre, $avatar]);
            $usuario = ['usuario_id' => (int)$pdo->lastInsertId(), 'nombre' => $nombre, 'avatar' => $avatar];
            $log->info("Usuario creado | Nombre: $nombre | ID: {$usuario['usuario_id']}");
        }
 
        $stmt = $pdo->prepare('INSERT INTO logs_db (usuario_id, accion, detalle) VALUES (?, ?, ?)');
        $stmt->execute([$usuario['usuario_id'], 'LOGIN', "Inicio de sesión: {$usuario['nombre']}"]);
        $log->info("Login | Usuario: {$usuario['nombre']}");
 
        $_SESSION['usuario_id'] = (int)$usuario['usuario_id'];
        $_SESSION['nombre']     = $usuario['nombre'];
        $_SESSION['avatar']     = $usuario['avatar'];
        header('Location: game.php');
        exit;
    }
}
 
$recientes = $pdo->query("SELECT nombre, avatar FROM usuarios ORDER BY usuario_id DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TriviaScore — ¿Quién está jugando?</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0d0d1a; --card:#16162a; --border:#2a2a4a; --purple:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body {
    font-family:'Nunito',sans-serif; background:var(--bg); color:var(--text);
    min-height:100vh; display:flex; flex-direction:column;
  }
  body::before {
    content:''; position:fixed; inset:0;
    background: radial-gradient(ellipse 60% 40% at 20% 20%,rgba(124,58,237,.25) 0%,transparent 60%),
                radial-gradient(ellipse 50% 50% at 80% 80%,rgba(6,182,212,.2) 0%,transparent 60%);
    pointer-events:none;
  }
  nav {
    display:flex; align-items:center; justify-content:space-between;
    padding:1rem 2rem; background:rgba(13,13,26,.9);
    backdrop-filter:blur(12px); border-bottom:1px solid var(--border);
  }
  .logo { font-family:'Fredoka One',cursive; font-size:1.6rem;
    background:linear-gradient(90deg,var(--purple),var(--cyan));
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .nav-links { display:flex; gap:.5rem; }
  .nav-links a { text-decoration:none; color:var(--muted); font-size:.9rem; font-weight:600;
    padding:.4rem .9rem; border-radius:8px; transition:all .2s; }
  .nav-links a:hover, .nav-links a.active { background:var(--purple); color:#fff; }
 
  .hero {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:3rem 1rem; text-align:center;
    animation:fadeUp .6s ease both;
  }
  @keyframes fadeUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
  .emoji-big { font-size:4rem; display:block; margin-bottom:1rem; animation:bounce 2s infinite; }
  @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
 
  h1 { font-family:'Fredoka One',cursive; font-size:clamp(2rem,6vw,3.5rem); margin-bottom:.5rem;
    background:linear-gradient(90deg,#fff,var(--cyan));
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .subtitle { color:var(--muted); font-size:1.1rem; margin-bottom:2rem; }
 
  .card {
    background:var(--card); border:1px solid var(--border); border-radius:20px;
    padding:2.5rem 2rem; width:min(420px,90vw);
    box-shadow:0 0 60px rgba(124,58,237,.15);
  }
  .input-wrap { position:relative; margin-bottom:1.5rem; }
  .input-wrap .icon { position:absolute; left:1rem; top:50%; transform:translateY(-50%); font-size:1.3rem; pointer-events:none; }
  input[type="text"] {
    width:100%; padding:.9rem 1rem .9rem 3rem;
    background:rgba(255,255,255,.05); border:2px solid var(--border);
    border-radius:12px; color:var(--text); font-family:'Nunito',sans-serif;
    font-size:1.1rem; font-weight:600; outline:none;
    transition:border-color .2s,box-shadow .2s;
  }
  input[type="text"]::placeholder { color:var(--muted); font-weight:400; }
  input[type="text"]:focus { border-color:var(--purple); box-shadow:0 0 0 4px rgba(124,58,237,.2); }
 
  .btn-play {
    width:100%; padding:1rem;
    background:linear-gradient(135deg,var(--purple),var(--cyan));
    border:none; border-radius:12px; color:#fff;
    font-family:'Fredoka One',cursive; font-size:1.3rem; cursor:pointer;
    transition:transform .15s,box-shadow .15s;
    box-shadow:0 4px 24px rgba(124,58,237,.4);
    display:flex; align-items:center; justify-content:center; gap:.5rem;
  }
  .btn-play:hover { transform:translateY(-2px); box-shadow:0 8px 32px rgba(124,58,237,.5); }
  .btn-play:active { transform:scale(.97); }
 
  .divider { display:flex; align-items:center; gap:1rem; margin:1.5rem 0; color:var(--muted); font-size:.85rem; }
  .divider::before,.divider::after { content:''; flex:1; height:1px; background:var(--border); }
 
  .users-grid { display:flex; flex-wrap:wrap; gap:.6rem; justify-content:center; }
  .user-pill {
    background:rgba(255,255,255,.05); border:1px solid var(--border);
    border-radius:30px; padding:.35rem .9rem; font-size:.85rem; font-weight:600;
    cursor:pointer; transition:all .2s; color:var(--muted);
  }
  .user-pill:hover { background:var(--purple); color:#fff; border-color:var(--purple); transform:scale(1.05); }
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
    <form method="POST">
      <div class="input-wrap">
        <span class="icon">✏️</span>
        <input type="text" name="nombre" placeholder="Tu nombre aquí..." maxlength="80" autofocus autocomplete="off" required>
      </div>
      <button type="submit" class="btn-play">▶ ¡Jugar ahora!</button>
    </form>
 
    <?php if (count($recientes) > 0): ?>
    <div class="divider">o elige un jugador reciente</div>
    <div class="users-grid">
      <?php foreach ($recientes as $u): ?>
        <span class="user-pill" onclick="document.querySelector('input[name=nombre]').value='<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>'">
          <?= $u['avatar'] ?> <?= htmlspecialchars($u['nombre']) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>