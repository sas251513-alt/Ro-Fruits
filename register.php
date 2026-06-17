<?php
session_start();
include '../includes/db_connect.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    if ($stmt->execute()) {
        $success = "Account created! You can now login.";
    } else {
        $error = "Username or email already exists.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; display:flex; flex-direction:column; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; text-shadow:0 0 15px rgba(255,184,0,0.4); }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }
 
        .auth-page { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 20px;
            background: radial-gradient(ellipse at 70% 50%, rgba(255,184,0,0.05) 0%, transparent 60%),
                        radial-gradient(ellipse at 30% 50%, rgba(0,191,255,0.04) 0%, transparent 60%); }
        .auth-card { background:#12182B; border:1px solid rgba(255,255,255,0.07); border-radius:20px; overflow:hidden; display:flex; width:100%; max-width:820px; box-shadow:0 30px 60px rgba(0,0,0,0.5); }
 
        .auth-left { flex:1; padding:50px 40px; background:linear-gradient(135deg,#0f1628 0%,#12182B 50%,#0a0f1e 100%); border-right:1px solid rgba(255,255,255,0.06); display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
        .auth-left-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:30px; text-shadow:0 0 30px rgba(255,184,0,0.4); margin-bottom:12px; }
        .auth-left-sub { font-size:13px; color:rgba(255,255,255,0.35); line-height:1.7; margin-bottom:28px; }
        .auth-fruits { font-size:44px; display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-bottom:24px; }
        .perks { text-align:left; width:100%; }
        .perk { display:flex; align-items:center; gap:10px; font-size:13px; color:rgba(255,255,255,0.5); margin-bottom:10px; }
        .perk-icon { color:#FFB800; width:20px; text-align:center; }
 
        .auth-right { flex:1; padding:50px 40px; }
        .auth-title { font-family:'Cinzel Decorative',serif; font-size:22px; color:#FFB800; margin-bottom:6px; }
        .auth-subtitle { font-size:13px; color:rgba(255,255,255,0.35); margin-bottom:28px; }
 
        .success-box { background:rgba(40,167,69,0.1); border:1px solid rgba(40,167,69,0.3); color:#4CAF50; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        .error-box   { background:rgba(220,53,69,0.1); border:1px solid rgba(220,53,69,0.3); color:#ff6b6b; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; }
 
        .field { margin-bottom:18px; }
        .field label { display:block; font-size:13px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:7px; }
        .input-wrap { position:relative; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,0.25); font-size:14px; }
        .input-wrap input { width:100%; background:#0B0F1A; border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; padding:12px 14px 12px 42px; font-family:'Exo 2',sans-serif; font-size:14px; transition:border-color 0.2s; }
        .input-wrap input:focus { outline:none; border-color:#FFB800; }
        .input-wrap input::placeholder { color:rgba(255,255,255,0.2); }
 
        .btn-auth { width:100%; background:#FFB800; color:#0B0F1A; border:none; padding:14px; font-size:15px; font-weight:700; font-family:'Exo 2',sans-serif; border-radius:10px; cursor:pointer; letter-spacing:0.5px; transition:background 0.2s,transform 0.15s; margin-top:4px; }
        .btn-auth:hover { background:#FFC933; transform:scale(1.01); }
        .auth-switch { text-align:center; margin-top:18px; font-size:13px; color:rgba(255,255,255,0.35); }
        .auth-switch a { color:#FFB800; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="../shop.php">Shop</a>
        <a href="login.php">Login</a>
    </div>
</nav>
 
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-left">
            <div class="auth-fruits">🧘⚡🌀☠️🐉</div>
            <div class="auth-left-logo">Ro-Fruit</div>
            <p class="auth-left-sub">Join thousands of players<br>powering up with Ro-Fruit.</p>
            <div class="perks">
                <div class="perk"><span class="perk-icon">✓</span>Track your orders anytime</div>
                <div class="perk"><span class="perk-icon">✓</span>Exclusive promo codes</div>
                <div class="perk"><span class="perk-icon">✓</span>Fast in-game delivery</div>
                <div class="perk"><span class="perk-icon">✓</span>Access to rare Divine fruits</div>
            </div>
        </div>
        <div class="auth-right">
            <div class="auth-title">Create Account</div>
            <div class="auth-subtitle">Join Ro-Fruit and start your journey</div>
 
            <?php if ($success): ?><div class="success-box">✓ <?php echo htmlspecialchars($success); ?> <a href="login.php" style="color:#4CAF50;font-weight:700;">Login now →</a></div><?php endif; ?>
            <?php if ($error): ?><div class="error-box">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
 
            <form method="POST">
                <div class="field">
                    <label>Username</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Choose a username" required value="<?php echo isset($_POST['username'])?htmlspecialchars($_POST['username']):''; ?>">
                    </div>
                </div>
                <div class="field">
                    <label>Email</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Your email address" required value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>">
                    </div>
                </div>
                <div class="field">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Create a password" required>
                    </div>
                </div>
                <button type="submit" class="btn-auth">Create Account →</button>
            </form>
            <div class="auth-switch">Already have an account? <a href="login.php">Login here</a></div>
        </div>
    </div>
</div>
<?php $base = '../'; include '../includes/footer.php'; ?>
</body>
</html>