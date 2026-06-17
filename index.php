<?php
session_start();
include 'includes/db_connect.php';
// Unread messages count
$unread_msgs = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $uc  = $conn->query("SELECT COUNT(*) c FROM messages m LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=$uid WHERE m.is_global=1 AND mr.id IS NULL");
    if ($uc) $unread_msgs = $uc->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ro-Fruit - Blox Fruits Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; overflow-x:hidden; }
 
        /* Announcement bar */
        .announce-bar {
            background: linear-gradient(90deg, #1a0508, #2d0810, #1a0508);
            border-bottom: 1px solid rgba(255,77,109,0.4);
            padding: 10px 50px 10px 20px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            position: relative;
        }
        @keyframes fire-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.3)} }
        .announce-bar .fire { display:inline-block; animation:fire-pulse 1s ease-in-out infinite; }
        .announce-bar a { color:#FF4D6D; text-decoration:none; margin-left:8px; border-bottom:1px solid rgba(255,77,109,0.4); }
        .announce-bar a:hover { border-color:#FF4D6D; }
        .announce-close { position:absolute; right:16px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.4); font-size:16px; cursor:pointer; transition:color 0.2s; }
        .announce-close:hover { color:#fff; }
 
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:22px; text-decoration:none; text-shadow:0 0 20px rgba(255,184,0,0.5); margin-right:6px; }
        .nav-left  { display:flex; align-items:center; gap:24px; }
        .nav-right { display:flex; align-items:center; gap:22px; }
        .nav-left a:not(.nav-logo) { color:#aaa; text-decoration:none; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-left a:not(.nav-logo):hover { color:#FFB800; }
        .nav-right a { color:#aaa; text-decoration:none; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-right a:hover { color:#FFB800; }
        .cart-badge { background:#FFB800; color:#0B0F1A; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700; }
 
        .hero { min-height:calc(100vh - 40px); display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:60px 20px 60px;
            background: radial-gradient(ellipse at 15% 50%,rgba(0,191,255,0.06) 0%,transparent 50%), radial-gradient(ellipse at 85% 20%,rgba(255,184,0,0.08) 0%,transparent 45%), radial-gradient(ellipse at 50% 90%,rgba(168,85,247,0.06) 0%,transparent 50%); }
        .eyebrow { font-size:11px; letter-spacing:5px; text-transform:uppercase; color:#FFB800; margin-bottom:18px; font-weight:600; }
        .hero-title { font-family:'Cinzel Decorative',serif; font-size:clamp(56px,12vw,104px); font-weight:900; line-height:1; text-shadow:0 0 40px rgba(255,184,0,0.35),0 0 90px rgba(255,184,0,0.08); margin-bottom:8px; }
        .hero-title .dash { color:#FFB800; }
        .hero-sub { font-size:15px; color:rgba(255,255,255,0.45); margin:18px 0 52px; letter-spacing:0.5px; font-style:italic; }
 
        .categories { display:flex; gap:22px; justify-content:center; flex-wrap:wrap; }
        .cat-card { background:#12182B; border:1px solid rgba(255,255,255,0.07); border-radius:18px; padding:38px 28px; width:220px; text-align:center; text-decoration:none; color:#fff; transition:all 0.3s cubic-bezier(0.25,0.8,0.25,1); position:relative; overflow:hidden; }
        .cat-card::after { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:var(--accent); opacity:0; transition:opacity 0.3s; }
        .cat-card:hover { border-color:var(--accent); transform:translateY(-8px); box-shadow:0 24px 48px rgba(0,0,0,0.5),0 0 24px var(--glow); }
        .cat-card:hover::after { opacity:1; }
        .cat-icon { font-size:52px; margin-bottom:16px; display:block; }
        .cat-name { font-family:'Cinzel Decorative',serif; font-size:13px; font-weight:700; color:var(--accent); margin-bottom:8px; letter-spacing:1px; }
        .cat-desc { font-size:12px; color:rgba(255,255,255,0.35); line-height:1.6; margin-bottom:12px; }
        .cat-range { font-size:13px; font-weight:700; color:var(--accent); opacity:0.8; }
 
        .browse-all { margin-top:38px; font-size:14px; color:rgba(255,255,255,0.3); }
        .browse-all a { color:#00BFFF; text-decoration:none; font-weight:600; border-bottom:1px solid rgba(0,191,255,0.3); }
        .browse-all a:hover { border-color:#00BFFF; }
 
        .socials-section { margin-top:48px; text-align:center; }
        .socials-label { font-size:12px; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.25); margin-bottom:14px; font-weight:600; }
        .socials-label span { color:#FFB800; }
        .socials { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        .social-btn { display:flex; align-items:center; gap:9px; padding:11px 20px; border-radius:30px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.55); text-decoration:none; font-size:14px; font-weight:600; font-family:'Exo 2',sans-serif; transition:all 0.25s; }
        .social-btn i { font-size:17px; }
        .social-btn:hover { background:var(--brand); border-color:var(--brand); color:#fff; transform:translateY(-3px); box-shadow:0 10px 24px rgba(0,0,0,0.4),0 0 16px var(--glow); }
    </style>
</head>
<body>
 
<!-- ── Loading Spinner ── -->
<div id="page-loader">
    <div class="loader-inner">
        <div class="loader-logo">Ro<span>-</span>Fruit</div>
        <div class="loader-ring"></div>
    </div>
</div>
<style>
#page-loader { position:fixed;top:0;left:0;width:100%;height:100%;background:#0B0F1A;z-index:99999;display:flex;align-items:center;justify-content:center;transition:opacity 0.5s ease; }
#page-loader.done { opacity:0;pointer-events:none; }
.loader-inner { text-align:center; }
.loader-logo { font-family:'Cinzel Decorative',serif;color:#FFB800;font-size:38px;text-shadow:0 0 30px rgba(255,184,0,0.5);margin-bottom:22px; }
.loader-logo span { color:#fff; }
.loader-ring { width:46px;height:46px;border:3px solid rgba(255,184,0,0.15);border-top:3px solid #FFB800;border-radius:50%;animation:spin-loader 0.75s linear infinite;margin:0 auto; }
@keyframes spin-loader { to { transform:rotate(360deg); } }
</style>
<script>
window.addEventListener('load',function(){
    var l=document.getElementById('page-loader');
    if(l){ setTimeout(function(){ l.classList.add('done'); setTimeout(function(){ l.style.display='none'; },500); },200); }
});
</script>
 
<!-- Announcement Bar -->
<div class="announce-bar" id="announceBanner">
    <span class="fire">🔥</span>
    Limited stock on Divine fruits! Only a few Dragon & Kitsune left.
    <a href="shop.php?cat=3">Shop Divine →</a>
    <button class="announce-close" onclick="document.getElementById('announceBanner').style.display='none'">✕</button>
</div>
 
<nav>
    <div class="nav-left">
        <a href="index.php" class="nav-logo">Ro-Fruit</a>
        <a href="index.php">Home</a>
        <a href="shop.php">Shop</a>
    </div>
    <div class="nav-right">
        <?php if (isset($_SESSION['username'])): ?>
            <span style="color:#FFB800;">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="pages/orders.php">📋 My Orders</a>
            <a href="pages/game.php" style="color:#FFB800;font-weight:700;">🎲 Lucky Box</a>
            <a href="pages/inbox.php" style="position:relative;">🔔
                <?php if ($unread_msgs > 0): ?>
                    <span style="position:absolute;top:-6px;right:-8px;background:#ff4444;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:10px;line-height:1.4;"><?php echo $unread_msgs; ?></span>
                <?php endif; ?>
            </a>
            <a href="pages/cart.php">🛒 Cart
                <?php if (!empty($_SESSION['cart'])): ?>
                    <span class="cart-badge"><?php echo array_sum($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            <a href="pages/logout.php">Logout</a>
        <?php else: ?>
            <a href="pages/login.php">Login</a>
            <a href="pages/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
 
<section class="hero">
    <p class="eyebrow">✦ The #1 Blox Fruits Item Shop ✦</p>
    <h1 class="hero-title">Ro<span class="dash">-</span>Fruit</h1>
    <p class="hero-sub">"The rarest fruits, delivered straight to your Roblox account."</p>
 
    <div class="categories">
        <a href="shop.php?cat=1" class="cat-card" style="--accent:#FFB800;--glow:rgba(255,184,0,0.15);">
            <span class="cat-icon">🌟</span>
            <div class="cat-name">Legendary</div>
            <div class="cat-desc">11 powerful fruits for every playstyle</div>
            <div class="cat-range">NT$15 – NT$129</div>
        </a>
        <a href="shop.php?cat=2" class="cat-card" style="--accent:#A855F7;--glow:rgba(168,85,247,0.15);">
            <span class="cat-icon">🔮</span>
            <div class="cat-name">Mythic</div>
            <div class="cat-desc">6 rare fruits with devastating power</div>
            <div class="cat-range">NT$99 – NT$399</div>
        </a>
        <a href="shop.php?cat=3" class="cat-card" style="--accent:#FF4D6D;--glow:rgba(255,77,109,0.2);">
            <span class="cat-icon">👑</span>
            <div class="cat-name">Divine</div>
            <div class="cat-desc">The rarest fruits in existence</div>
            <div class="cat-range">NT$699 – NT$999</div>
        </a>
    </div>
 
    <p class="browse-all">or <a href="shop.php">browse all fruits</a></p>
 
    <div class="socials-section">
        <p class="socials-label">Follow us for <span>exclusive promo codes</span> 🎟️</p>
        <div class="socials">
            <a href="https://discord.gg/YHVsF38J" target="_blank" class="social-btn" style="--brand:#5865F2;--glow:rgba(88,101,242,0.35);"><i class="fab fa-discord"></i> Discord</a>
            <a href="https://www.instagram.com/tipen_as/" target="_blank" class="social-btn" style="--brand:#E1306C;--glow:rgba(225,48,108,0.35);"><i class="fab fa-instagram"></i> Instagram</a>
            <a href="https://youtu.be/dQw4w9WgXcQ?si=Ouawnnle0TSpNBYN" target="_blank" class="social-btn" style="--brand:#FF0000;--glow:rgba(255,0,0,0.3);"><i class="fab fa-youtube"></i> YouTube</a>
        </div>
    </div>
</section>
<?php $base = ''; include 'includes/footer.php'; ?>
</body>
</html>