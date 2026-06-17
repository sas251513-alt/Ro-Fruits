<?php
session_start();
include 'includes/db_connect.php';
 
// Unread messages count for bell
$unread_msgs = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $uc  = $conn->query("SELECT COUNT(*) c FROM messages m LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=$uid WHERE m.is_global=1 AND mr.id IS NULL");
    if ($uc) $unread_msgs = $uc->fetch_assoc()['c'];
}
 
$cat    = isset($_GET['cat'])    ? intval($_GET['cat'])        : 0;
$search = isset($_GET['search']) ? trim($_GET['search'])       : '';
$cat_names = [0=>'All Fruits',1=>'Legendary',2=>'Mythic',3=>'Divine'];
$page_title = $cat_names[$cat] ?? 'All Fruits';
 
// Build SQL with optional category + search
$where = [];
if ($cat > 0)      $where[] = "category_id = $cat";
if ($search !== '') $where[] = "name LIKE '%" . $conn->real_escape_string($search) . "%'";
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql    = "SELECT * FROM products $where_sql ORDER BY category_id ASC, price ASC";
$result = $conn->query($sql);
$count  = $result->num_rows;
 
$emojis = [
    'Phoenix Fruit'=>'🦅','Spider Fruit'=>'🕷️','Love Fruit'=>'💕','Buddha Fruit'=>'🧘',
    'Rumble Fruit'=>'⚡','Portal Fruit'=>'🌀','Blizzard Fruit'=>'❄️','Pain Fruit'=>'🐾',
    'Gravity Fruit'=>'🌌','Mammoth Fruit'=>'🦣','T-Rex Fruit'=>'🦖','Dough Fruit'=>'🍩',
    'Spirit Fruit'=>'👻','Venom Fruit'=>'☠️','Control Fruit'=>'🎮','Leopard Fruit'=>'🐆',
    'Yeti Fruit'=>'🧊','Dragon Fruit'=>'🐉','Kitsune Fruit'=>'🦊',
];
$rarity = [
    1=>['label'=>'LEGENDARY','color'=>'#FFD700','glow'=>'rgba(255,215,0,0.22)',  'bg'=>'rgba(255,215,0,0.07)'],
    2=>['label'=>'MYTHIC',   'color'=>'#E53535','glow'=>'rgba(220,30,30,0.22)',  'bg'=>'rgba(220,30,30,0.07)'],
    3=>['label'=>'DIVINE',   'color'=>'#FFFFFF', 'glow'=>'rgba(255,255,255,0.25)','bg'=>'rgba(255,255,255,0.05)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; }
 
        /* Announcement bar */
        .announce-bar { background:linear-gradient(90deg,#1a0508,#2d0810,#1a0508); border-bottom:1px solid rgba(255,77,109,0.4); padding:10px 50px 10px 20px; text-align:center; font-size:13px; font-weight:600; color:#fff; position:relative; }
        @keyframes fire-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.3)} }
        .announce-bar .fire { display:inline-block; animation:fire-pulse 1s ease-in-out infinite; }
        .announce-bar a { color:#FF4D6D; text-decoration:none; margin-left:8px; border-bottom:1px solid rgba(255,77,109,0.4); }
        .announce-close { position:absolute; right:16px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.4); font-size:16px; cursor:pointer; }
        .announce-close:hover { color:#fff; }
 
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; text-shadow:0 0 15px rgba(255,184,0,0.4); margin-right:6px; }
        .nav-left  { display:flex; align-items:center; gap:24px; }
        .nav-right { display:flex; align-items:center; gap:22px; }
        .nav-left a:not(.nav-logo) { color:#aaa; text-decoration:none; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-left a:not(.nav-logo):hover { color:#FFB800; }
        .nav-right a { color:#aaa; text-decoration:none; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-right a:hover { color:#FFB800; }
        .cart-badge { background:#FFB800; color:#0B0F1A; padding:2px 7px; border-radius:10px; font-size:11px; font-weight:700; }
 
        .flash-msg { background:rgba(40,167,69,0.12); border-bottom:1px solid rgba(40,167,69,0.3); color:#4CAF50; text-align:center; padding:11px; font-size:14px; font-weight:600; }
 
        /* Search + Tabs row */
        .controls { max-width:1200px; margin:28px auto 0; padding:0 30px; display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; }
        .tabs { display:flex; gap:8px; flex-wrap:wrap; }
        .tab { padding:9px 20px; border-radius:30px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:rgba(255,255,255,0.45); text-decoration:none; font-size:13px; font-weight:600; font-family:'Exo 2',sans-serif; letter-spacing:0.5px; transition:all 0.2s; }
        .tab.active-all  { background:#333;    color:#fff;    border-color:#555; }
        .tab.active-leg  { background:#FFD700; color:#0B0F1A; border-color:#FFD700; }
        .tab.active-myth { background:#E53535; color:#fff;    border-color:#E53535; }
        .tab.active-div  { background:#fff;    color:#0B0F1A; border-color:#fff; }
        /* Per-tab hover colors */
        .tab-leg:hover  { border-color:#FFD700 !important; color:#FFD700 !important; }
        .tab-myth:hover { border-color:#E53535 !important; color:#E53535 !important; }
        .tab-div:hover  { border-color:#ffffff !important; color:#ffffff !important; }
        .tab-all:hover  { border-color:rgba(255,255,255,0.5) !important; color:#fff !important; }
 
        /* Search bar */
        .search-form { display:flex; gap:0; }
        .search-input { background:#12182B; border:1px solid rgba(255,255,255,0.12); border-right:none; border-radius:8px 0 0 8px; color:#fff; padding:10px 16px; font-family:'Exo 2',sans-serif; font-size:14px; width:220px; transition:border-color 0.2s; }
        .search-input:focus { outline:none; border-color:#FFB800; }
        .search-btn { background:#FFB800; border:none; border-radius:0 8px 8px 0; padding:10px 16px; cursor:pointer; color:#0B0F1A; font-size:15px; font-weight:700; transition:background 0.2s; }
        .search-btn:hover { background:#FFC933; }
        .clear-search { margin-left:8px; color:rgba(255,255,255,0.35); text-decoration:none; font-size:13px; align-self:center; }
        .clear-search:hover { color:#fff; }
 
        .page-header { max-width:1200px; margin:20px auto 4px; padding:0 30px; }
        .page-header h2 { font-family:'Cinzel Decorative',serif; font-size:22px; }
        .search-tag { font-size:14px; color:rgba(255,255,255,0.4); font-weight:400; margin-left:10px; }
        .item-count { max-width:1200px; margin:4px auto 22px; padding:0 30px; color:rgba(255,255,255,0.3); font-size:13px; }
 
        /* Grid */
        .grid { max-width:1200px; margin:0 auto 70px; padding:0 30px; display:grid; grid-template-columns:repeat(auto-fill,minmax(215px,1fr)); gap:20px; }
        .card { background:#12182B; border:1px solid rgba(255,255,255,0.06); border-radius:14px; overflow:hidden; transition:all 0.3s; position:relative; }
        .card:hover { transform:translateY(-5px); }
        .card-leg:hover  { border-color:rgba(255,215,0,0.7);  box-shadow:0 18px 36px rgba(0,0,0,0.4),0 0 24px rgba(255,215,0,0.28); }
        /* Mythic — no persistent border, subtle red on hover only */
        .card-myth {
            border: 1px solid rgba(255,255,255,0.06) !important;
        }
        .card-myth:hover {
            border: 1px solid rgba(220,30,30,0.3) !important;
            box-shadow: 0 18px 36px rgba(0,0,0,0.4), 0 0 20px rgba(220,30,30,0.15) !important;
        }
 
        /* Divine — no border, radiating holy light from background */
        .card-div {
            border: 1px solid rgba(255,255,255,0.07) !important;
            overflow: hidden;
            position: relative;
        }
        /* The holy light layer sits behind all card content */
        .card-div::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 260%; height: 260%;
            transform: translate(-50%, -50%) scale(0.8);
            background: radial-gradient(ellipse at center,
                rgba(255,255,255,0.42) 0%,
                rgba(255,253,220,0.28) 15%,
                rgba(255,248,190,0.15) 35%,
                rgba(255,242,160,0.06) 55%,
                transparent 70%
            );
            animation: holy-radiate 3s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        /* Keep all card content above the glow */
        /* Badges must keep position:absolute — only add z-index */
        .card-div .rarity-badge,
        .card-div .stock-badge { z-index: 1; }
        /* Non-absolute elements need position:relative to accept z-index */
        .card-div .card-img,
        .card-div .card-body { position: relative; z-index: 1; }
        .card-div:hover {
            border: 1px solid rgba(255,255,255,0.18) !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5) !important;
        }
        .card-div:hover::before {
            animation: holy-radiate-hover 1.8s ease-in-out infinite;
        }
        @keyframes holy-radiate {
            0%,100% { opacity:0.6; transform:translate(-50%,-50%) scale(0.78); }
            50%      { opacity:1;   transform:translate(-50%,-50%) scale(1.18); }
        }
        @keyframes holy-radiate-hover {
            0%,100% { opacity:0.85; transform:translate(-50%,-50%) scale(0.85); }
            50%      { opacity:1;    transform:translate(-50%,-50%) scale(1.28); }
        }
 
        /* LOW STOCK — red flash for legendary/mythic, white for divine */
        @keyframes low-stock-flash {
            0%,100% { border-color:rgba(255,40,40,0.3); box-shadow:0 0 6px rgba(255,40,40,0.15); }
            50%      { border-color:rgba(255,40,40,0.9); box-shadow:0 0 18px rgba(255,40,40,0.4); }
        }
        @keyframes low-stock-flash-div {
            0%,100% { box-shadow:0 0 6px rgba(255,255,255,0.2); }
            50%      { box-shadow:0 0 20px rgba(255,255,255,0.55), 0 0 40px rgba(255,248,200,0.2); }
        }
        .card-low     { animation:low-stock-flash     1.2s ease-in-out infinite !important; }
        .card-low-div { animation:low-stock-flash-div 1.2s ease-in-out infinite !important; }
 
        .card-img { width:100%; height:155px; display:flex; align-items:center; justify-content:center; font-size:66px; }
        .rarity-badge { position:absolute; top:10px; left:10px; padding:3px 9px; border-radius:6px; font-size:10px; font-weight:700; letter-spacing:1px; }
 
        /* Stock badge - flashing red when low */
        @keyframes badge-flash { 0%,100%{opacity:1} 50%{opacity:0.5} }
        .stock-badge { position:absolute; top:10px; right:10px; padding:3px 9px; border-radius:6px; font-size:11px; font-weight:700; }
        .badge-low  { background:rgba(220,40,40,0.9); color:#fff; animation:badge-flash 0.8s ease-in-out infinite; }
        .badge-ok   { background:rgba(40,167,69,0.82); color:#fff; }
 
        .card-body { padding:15px; }
        .card-name { font-weight:700; font-size:15px; margin-bottom:5px; }
        .card-desc { font-size:12px; color:rgba(255,255,255,0.38); line-height:1.55; margin-bottom:12px; min-height:37px; }
        .card-price { font-size:21px; font-weight:700; margin-bottom:13px; }
        .btn-add { display:block; text-align:center; padding:10px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:700; letter-spacing:0.5px; transition:filter 0.2s,transform 0.15s; }
        .btn-add:hover { filter:brightness(1.15); transform:scale(1.02); }
        .btn-sold { display:block; background:#1E2640; color:rgba(255,255,255,0.25); text-align:center; padding:10px; border-radius:8px; font-size:13px; font-weight:700; }
                /* Toast notification */
        .toast { position:fixed; bottom:24px; right:24px; background:#12182B; border:1px solid rgba(255,184,0,0.4); color:#4CAF50; padding:14px 20px; border-radius:12px; font-size:14px; font-weight:600; z-index:9999; box-shadow:0 8px 24px rgba(0,0,0,0.5); display:flex; align-items:center; gap:10px; transform:translateX(120%); transition:transform 0.35s cubic-bezier(0.25,0.8,0.25,1); }
        .toast.show { transform:translateX(0); }
        .toast-icon { font-size:20px; }
        /* Out of stock — greyed out card */
        .card-oos { opacity:0.42; filter:grayscale(0.55); }
        .card-oos:hover { transform:none !important; box-shadow:none !important; cursor:default; }
        .card-oos .card-img { filter:grayscale(0.4); }
        .empty { grid-column:1/-1; text-align:center; padding:80px 20px; color:rgba(255,255,255,0.25); font-size:16px; }
 
        /* ── PRODUCT MODAL ── */
        .modal-overlay {
            position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.8);
            display:flex; align-items:center; justify-content:center;
            z-index:2000; opacity:0; pointer-events:none;
            transition:opacity 0.25s;
            backdrop-filter:blur(6px);
        }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal-card {
            background:#12182B;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:20px; overflow:hidden;
            display:grid; grid-template-columns:1fr 1fr;
            max-width:680px; width:92%;
            max-height:90vh;
            position:relative;
            transform:scale(0.88) translateY(24px);
            transition:transform 0.3s cubic-bezier(0.25,0.8,0.25,1);
            box-shadow:0 40px 80px rgba(0,0,0,0.7);
        }
        .modal-overlay.open .modal-card { transform:scale(1) translateY(0); }
        .modal-close {
            position:absolute; top:14px; right:14px;
            background:rgba(255,255,255,0.08); border:none;
            color:#fff; width:32px; height:32px; border-radius:50%;
            font-size:16px; cursor:pointer; z-index:10;
            display:flex; align-items:center; justify-content:center;
            transition:background 0.2s;
        }
        .modal-close:hover { background:rgba(255,255,255,0.18); }
        .modal-visual {
            display:flex; align-items:center; justify-content:center;
            font-size:110px; padding:40px; position:relative; overflow:hidden;
        }
        .modal-visual .m-emoji { position:relative; z-index:1; }
        .modal-visual.divine-glow::before {
            content:''; position:absolute; top:50%; left:50%;
            width:250%; height:250%;
            transform:translate(-50%,-50%);
            background:radial-gradient(ellipse at center,
                rgba(255,255,255,0.35) 0%,rgba(255,252,200,0.2) 20%,transparent 65%);
            animation:holy-radiate 3s ease-in-out infinite;
        }
        .modal-info { padding:36px 32px; display:flex; flex-direction:column; justify-content:center; overflow-y:auto; }
        .modal-rarity { display:inline-block; padding:3px 12px; border-radius:20px; font-size:10px; font-weight:700; letter-spacing:1.5px; margin-bottom:12px; width:fit-content; }
        .modal-name { font-family:'Cinzel Decorative',serif; font-size:22px; margin-bottom:10px; line-height:1.2; }
        .modal-desc { font-size:13px; color:rgba(255,255,255,0.45); line-height:1.7; margin-bottom:20px; }
        .modal-price { font-size:34px; font-weight:700; margin-bottom:6px; }
        .modal-stock { font-size:13px; margin-bottom:22px; }
        .modal-btn-add {
            display:block; text-align:center; padding:13px;
            border-radius:10px; text-decoration:none;
            font-size:14px; font-weight:700; letter-spacing:0.5px;
            transition:filter 0.2s,transform 0.15s; margin-bottom:10px;
        }
        .modal-btn-add:hover { filter:brightness(1.12); transform:scale(1.02); }
        .modal-btn-close-link { display:block; text-align:center; color:rgba(255,255,255,0.3); font-size:13px; text-decoration:none; cursor:pointer; }
        .modal-btn-close-link:hover { color:#fff; }
        @media(max-width:560px){ .modal-card{grid-template-columns:1fr;} .modal-visual{padding:30px;font-size:80px;} }
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
 
<!-- Announcement -->
<div class="announce-bar" id="announceBanner">
    <span class="fire">🔥</span>
    Limited stock on Divine fruits! Only a few Dragon &amp; Kitsune left.
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
 
<?php
// Toast notification from session
$last_added = '';
if (isset($_SESSION['last_added'])) {
    $last_added = $_SESSION['last_added'];
    unset($_SESSION['last_added']);
}
?>
 
<!-- Controls: Tabs + Search -->
<div class="controls">
    <div class="tabs">
        <a href="shop.php<?php echo $search?'?search='.urlencode($search):''; ?>"        class="tab tab-all  <?php echo $cat==0?'active-all':''; ?>">🍑 All</a>
        <a href="shop.php?cat=1<?php echo $search?'&search='.urlencode($search):''; ?>"  class="tab tab-leg  <?php echo $cat==1?'active-leg':''; ?>">🌟 Legendary</a>
        <a href="shop.php?cat=2<?php echo $search?'&search='.urlencode($search):''; ?>"  class="tab tab-myth <?php echo $cat==2?'active-myth':''; ?>">🔮 Mythic</a>
        <a href="shop.php?cat=3<?php echo $search?'&search='.urlencode($search):''; ?>"  class="tab tab-div  <?php echo $cat==3?'active-div':''; ?>">👑 Divine</a>
    </div>
 
    <form method="GET" action="shop.php" class="search-form">
        <?php if ($cat > 0): ?><input type="hidden" name="cat" value="<?php echo $cat; ?>"><?php endif; ?>
        <input type="text" name="search" class="search-input"
               placeholder="Search fruits..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-btn">🔍</button>
        <?php if ($search): ?>
            <a href="shop.php<?php echo $cat?'?cat='.$cat:''; ?>" class="clear-search">✕ Clear</a>
        <?php endif; ?>
    </form>
</div>
 
<div class="page-header">
    <h2><?php echo $page_title; ?>
        <?php if ($search): ?><span class="search-tag">results for "<?php echo htmlspecialchars($search); ?>"</span><?php endif; ?>
    </h2>
</div>
<p class="item-count"><?php echo $count; ?> fruit<?php echo $count!=1?'s':''; ?> found</p>
 
<div class="grid">
<?php
if ($count > 0):
    while ($p = $result->fetch_assoc()):
        $cid   = $p['category_id'];
        $r     = $rarity[$cid] ?? $rarity[1];
        $emoji = $emojis[$p['name']] ?? '🍑';
        $low   = $p['stock'] > 0 && $p['stock'] <= 3;
        $ok    = $p['stock'] > 0;
        $card_class = ['1'=>'card-leg','2'=>'card-myth','3'=>'card-div'][$cid] ?? 'card-leg';
        // Low stock: red flash for leg/myth, white flash for divine
        if ($low && $cid != 3) $card_class .= ' card-low';
        if ($low && $cid == 3) $card_class .= ' card-low-div';
        if (!$ok)              $card_class .= ' card-oos';
?>
    <div class="card <?php echo $card_class; ?>" style="cursor:pointer;"
         onclick="openModal(
             '<?php echo $p['id']; ?>',
             '<?php echo addslashes(htmlspecialchars($p['name'])); ?>',
             '<?php echo addslashes(htmlspecialchars($p['description'])); ?>',
             '<?php echo $p['price']; ?>',
             '<?php echo $emoji; ?>',
             '<?php echo $p['stock']; ?>',
             '<?php echo $r['color']; ?>',
             '<?php echo $r['label']; ?>',
             '<?php echo addslashes($r['bg']); ?>'
         )">
        <span class="rarity-badge" style="background:<?php echo $r['bg']; ?>;color:<?php echo $r['color']; ?>;border:1px solid <?php echo $r['color']; ?>33;"><?php echo $r['label']; ?></span>
        <?php if ($low): ?>
            <span class="stock-badge badge-low">⚠ Only <?php echo $p['stock']; ?> left!</span>
        <?php elseif ($ok): ?>
            <span class="stock-badge badge-ok">In Stock</span>
        <?php endif; ?>
        <div class="card-img" style="background:<?php echo $r['bg']; ?>;"><?php echo $emoji; ?></div>
        <div class="card-body">
            <div class="card-name"><?php echo htmlspecialchars($p['name']); ?></div>
            <div class="card-desc"><?php echo htmlspecialchars($p['description']); ?></div>
            <div class="card-price" style="color:<?php echo $r['color']; ?>;">NT$<?php echo number_format($p['price'],0); ?></div>
        </div>
        <div style="padding:0 15px 15px;" onclick="event.stopPropagation()">
            <?php if ($ok): ?>
                <button onclick="addToCart('<?php echo $p['id']; ?>','<?php echo addslashes($p['name']); ?>')" class="btn-add"
                   style="background:<?php echo $r['color']; ?>;color:<?php echo ($cid==1||$cid==3)?'#0B0F1A':'#fff'; ?>;border:none;cursor:pointer;width:100%;">+ Add to Cart</button>
            <?php else: ?>
                <div class="btn-sold">Out of Stock</div>
            <?php endif; ?>
        </div>
    </div>
<?php endwhile;
else: ?>
    <div class="empty">
        <?php if ($search): ?>No fruits found for "<?php echo htmlspecialchars($search); ?>". <a href="shop.php" style="color:#FFB800;">Clear search</a>
        <?php else: ?>No fruits in this category yet.<?php endif; ?>
    </div>
<?php endif; ?>
</div>
 
<!-- ── PRODUCT MODAL ── -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
    <div class="modal-card" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-visual" id="modalVisual">
            <span class="m-emoji" id="modalEmoji"></span>
        </div>
        <div class="modal-info">
            <span class="modal-rarity" id="modalRarity"></span>
            <h2 class="modal-name" id="modalName"></h2>
            <p class="modal-desc" id="modalDesc"></p>
            <div class="modal-price" id="modalPrice"></div>
            <div class="modal-stock" id="modalStock"></div>
            <button class="modal-btn-add" id="modalAddBtn" onclick="modalAddToCart()" style="border:none;cursor:pointer;width:100%;">🛒 Add to Cart</button>
            <span class="modal-btn-close-link" onclick="closeModal()">← Keep browsing</span>
        </div>
    </div>
</div>
 
<script>
function openModal(id, name, desc, price, emoji, stock, color, label, bg) {
    stock = parseInt(stock);
    document.getElementById('modalEmoji').textContent = emoji;
    var vis = document.getElementById('modalVisual');
    vis.style.background = bg;
    vis.className = 'modal-visual' + (label === 'DIVINE' ? ' divine-glow' : '');
 
    var rar = document.getElementById('modalRarity');
    rar.textContent = label;
    rar.style.color = color;
    rar.style.background = bg;
    rar.style.border = '1px solid ' + color + '44';
 
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalDesc').textContent = desc;
 
    var priceEl = document.getElementById('modalPrice');
    priceEl.textContent = 'NT$' + parseInt(price).toLocaleString();
    priceEl.style.color = color;
 
    var stockEl = document.getElementById('modalStock');
    if (stock === 0) {
        stockEl.innerHTML = '<span style="color:rgba(255,255,255,0.25)">✕ Out of Stock</span>';
    } else if (stock <= 3) {
        stockEl.innerHTML = '<span style="color:#ff4444;font-weight:700;">⚠ Only ' + stock + ' left — order soon!</span>';
    } else {
        stockEl.innerHTML = '<span style="color:#4CAF50">✓ In Stock (' + stock + ' available)</span>';
    }
 
    var btn = document.getElementById('modalAddBtn');
    window._modalProductId   = id;
    window._modalProductName = name;
    if (stock > 0) {
        btn.style.background = color;
        btn.style.color = (label === 'LEGENDARY' || label === 'DIVINE') ? '#0B0F1A' : '#fff';
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
 
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
 
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
 
// ── Add to cart without page reload ──
async function addToCart(id, name) {
    try {
        var res  = await fetch('pages/cart.php?add=' + id + '&ajax=1');
        var data = await res.json();
        if (data.success) {
            // Update every cart badge on the page
            document.querySelectorAll('.cart-badge').forEach(function(el) {
                el.textContent = data.cart_count;
                el.style.display = 'inline';
            });
            // If no badge exists yet, add one next to Cart link
            var cartLinks = document.querySelectorAll('a[href*="cart.php"]');
            cartLinks.forEach(function(lnk) {
                if (!lnk.querySelector('.cart-badge')) {
                    var b = document.createElement('span');
                    b.className = 'cart-badge';
                    b.textContent = data.cart_count;
                    lnk.appendChild(b);
                }
            });
            showToast(data.name + ' added to cart!');
        }
    } catch(e) {
        // Fallback if fetch fails
        window.location.href = 'pages/cart.php?add=' + id;
    }
}
 
function modalAddToCart() {
    if (window._modalProductId) {
        addToCart(window._modalProductId, window._modalProductName);
        closeModal();
    }
}
</script>
 
<!-- Toast notification -->
<div class="toast" id="toast">
    <span class="toast-icon">🛒</span>
    <span id="toast-msg"></span>
</div>
<script>
var lastAdded = <?php echo json_encode($last_added); ?>;
function showToast(msg) {
    var t = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 2800);
}
if (lastAdded) showToast(lastAdded + ' added to cart!');
</script>
<?php $base = ''; include 'includes/footer.php'; ?>
</body>
</html>