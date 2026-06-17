<?php
session_start();

// ── Admin credentials — change these! ──
define('ADMIN_USER', 'AdminUser');
define('ADMIN_PASS', 'ADMINBEDES');

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php'); exit;
    } else {
        $error = 'Wrong username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center;
            background: radial-gradient(ellipse at 30% 50%, rgba(255,184,0,0.06) 0%,transparent 60%), #0B0F1A; }
        .card { background:#12182B; border:1px solid rgba(255,184,0,0.2); border-radius:18px; padding:48px 40px; width:100%; max-width:400px; box-shadow:0 30px 60px rgba(0,0,0,0.5); }
        .logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:26px; text-align:center; margin-bottom:6px; text-shadow:0 0 20px rgba(255,184,0,0.4); }
        .subtitle { text-align:center; font-size:12px; letter-spacing:3px; text-transform:uppercase; color:rgba(255,255,255,0.3); margin-bottom:36px; }
        .error { background:rgba(220,53,69,0.1); border:1px solid rgba(220,53,69,0.3); color:#ff6b6b; padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        label { display:block; font-size:13px; font-weight:600; color:rgba(255,255,255,0.5); margin-bottom:7px; }
        input { width:100%; background:#0B0F1A; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; padding:12px 14px; font-family:'Exo 2',sans-serif; font-size:14px; margin-bottom:18px; transition:border-color 0.2s; }
        input:focus { outline:none; border-color:#FFB800; }
        .btn { width:100%; background:#FFB800; color:#0B0F1A; border:none; padding:14px; font-size:15px; font-weight:700; font-family:'Exo 2',sans-serif; border-radius:8px; cursor:pointer; transition:background 0.2s; }
        .btn:hover { background:#FFC933; }
        .back { display:block; text-align:center; margin-top:16px; color:rgba(255,255,255,0.3); font-size:13px; text-decoration:none; }
        .back:hover { color:#FFB800; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">Ro-Fruit</div>
    <div class="subtitle">🔐 Admin Panel</div>
    <?php if ($error): ?><div class="error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" required autocomplete="off">
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn">Login to Admin Panel →</button>
    </form>
    <a href="../index.php" class="back">← Back to Store</a>
</div>
</body>
</html>
