<?php
session_start();
include '../includes/db_connect.php';
 
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$error = '';
 
// Fetch user's Lucky Box history
$history = $conn->query("
    SELECT gk.won_code, gk.used_at, pc.discount_type, pc.discount_value
    FROM game_keys gk
    LEFT JOIN promo_codes pc ON pc.code = gk.won_code
    WHERE gk.used_by_user = $uid AND gk.won_code IS NOT NULL
    ORDER BY gk.used_at DESC
");
 
// ── AJAX: Pick a box ──────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'pick') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['game_prizes'], $_SESSION['game_key'])) {
        echo json_encode(['error'=>'No active game.']); exit;
    }
    $chosen = intval($_GET['box']);
    $prizes = $_SESSION['game_prizes'];
    $prize  = $prizes[$chosen];
    $key    = $_SESSION['game_key'];
 
    // Generate unique promo code
    $promo_code = 'WIN-' . strtoupper(substr(md5(uniqid(rand(),true)), 0, 6));
    $type  = $prize['type'];
    $value = floatval($prize['value']);
 
    // Add promo code to promo_codes table
    $s1 = $conn->prepare("INSERT INTO promo_codes (code, discount_type, discount_value) VALUES (?,?,?)");
    $s1->bind_param("ssd", $promo_code, $type, $value);
    $s1->execute();
 
    // Mark game key as used
    $s2 = $conn->prepare("UPDATE game_keys SET is_used=1, used_by_user=?, won_code=?, used_at=NOW() WHERE key_code=?");
    $s2->bind_param("iss", $uid, $promo_code, $key);
    $s2->execute();
 
    // Clear game session
    unset($_SESSION['game_prizes'], $_SESSION['game_key']);
 
    echo json_encode(['prizes'=>$prizes,'chosen'=>$chosen,'promo_code'=>$promo_code,'prize'=>$prize]);
    exit;
}
 
// ── POST: Validate key ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key_code'])) {
    $key_code = strtoupper(trim($_POST['key_code']));
    $stmt = $conn->prepare("SELECT * FROM game_keys WHERE key_code=? AND is_used=0");
    $stmt->bind_param("s", $key_code);
    $stmt->execute();
    $key_row = $stmt->get_result()->fetch_assoc();
    if (!$key_row) {
        $error = "Invalid or already used key. Check your inbox for the correct key.";
    } else {
        $prizes = [
            ['emoji'=>'🎁','label'=>'5% Off',    'type'=>'percent','value'=>5,  'color'=>'#4CAF50', 'bg'=>'rgba(76,175,80,0.12)'],
            ['emoji'=>'🎁','label'=>'5% Off',    'type'=>'percent','value'=>5,  'color'=>'#4CAF50', 'bg'=>'rgba(76,175,80,0.12)'],
            ['emoji'=>'🎁','label'=>'5% Off',    'type'=>'percent','value'=>5,  'color'=>'#4CAF50', 'bg'=>'rgba(76,175,80,0.12)'],
            ['emoji'=>'⭐','label'=>'10% Off',   'type'=>'percent','value'=>10, 'color'=>'#00BFFF', 'bg'=>'rgba(0,191,255,0.12)'],
            ['emoji'=>'⭐','label'=>'10% Off',   'type'=>'percent','value'=>10, 'color'=>'#00BFFF', 'bg'=>'rgba(0,191,255,0.12)'],
            ['emoji'=>'💫','label'=>'15% Off',   'type'=>'percent','value'=>15, 'color'=>'#A855F7', 'bg'=>'rgba(168,85,247,0.12)'],
            ['emoji'=>'💫','label'=>'15% Off',   'type'=>'percent','value'=>15, 'color'=>'#A855F7', 'bg'=>'rgba(168,85,247,0.12)'],
            ['emoji'=>'🌟','label'=>'20% Off',   'type'=>'percent','value'=>20, 'color'=>'#FFB800', 'bg'=>'rgba(255,184,0,0.12)'],
            ['emoji'=>'👑','label'=>'NT$50 Off', 'type'=>'fixed',  'value'=>50, 'color'=>'#FF4D6D', 'bg'=>'rgba(255,77,109,0.12)'],
        ];
        shuffle($prizes);
        $_SESSION['game_prizes'] = $prizes;
        $_SESSION['game_key']    = $key_code;
    }
}
 
$game_active = isset($_SESSION['game_prizes']);
$prizes_json = $game_active ? json_encode($_SESSION['game_prizes']) : '[]';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lucky Box - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; display:flex; flex-direction:column;
            background: radial-gradient(ellipse at 20% 30%, rgba(255,184,0,0.07) 0%,transparent 50%), radial-gradient(ellipse at 80% 70%, rgba(168,85,247,0.06) 0%,transparent 50%), #0B0F1A; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }
 
        .wrap { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 20px; }
 
        /* ── KEY ENTRY ── */
        .key-card { background:#12182B; border:1px solid rgba(255,184,0,0.25); border-radius:20px; padding:48px 40px; text-align:center; max-width:440px; width:100%; box-shadow:0 30px 60px rgba(0,0,0,0.5); }
        .key-icon { font-size:60px; margin-bottom:16px; display:block; }
        .key-title { font-family:'Cinzel Decorative',serif; font-size:22px; color:#FFB800; margin-bottom:8px; }
        .key-sub { font-size:14px; color:rgba(255,255,255,0.4); margin-bottom:28px; line-height:1.6; }
        .key-error { background:rgba(220,53,69,0.12); border:1px solid rgba(220,53,69,0.3); color:#ff6b6b; padding:12px; border-radius:8px; margin-bottom:18px; font-size:14px; }
        .key-input { width:100%; background:#0B0F1A; border:2px solid rgba(255,184,0,0.25); border-radius:10px; color:#FFB800; padding:14px 18px; font-family:'Exo 2',sans-serif; font-size:18px; font-weight:700; text-align:center; letter-spacing:3px; transition:border-color 0.2s; margin-bottom:16px; }
        .key-input:focus { outline:none; border-color:#FFB800; }
        .key-input::placeholder { color:rgba(255,184,0,0.3); letter-spacing:2px; font-size:14px; }
        .btn-enter { width:100%; background:#FFB800; color:#0B0F1A; border:none; padding:14px; font-size:15px; font-weight:700; font-family:'Exo 2',sans-serif; border-radius:10px; cursor:pointer; transition:background 0.2s; }
        .btn-enter:hover { background:#FFC933; }
 
        /* ── GAME GRID ── */
        .game-section { text-align:center; }
        .game-title { font-family:'Cinzel Decorative',serif; font-size:28px; color:#FFB800; margin-bottom:6px; text-shadow:0 0 30px rgba(255,184,0,0.4); }
        .game-sub { font-size:14px; color:rgba(255,255,255,0.4); margin-bottom:36px; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; max-width:440px; margin:0 auto; }
 
        /* Box flip animation */
        .box-wrap { perspective:800px; cursor:pointer; }
        .box-wrap.disabled { cursor:default; }
        .box-inner { width:130px; height:130px; position:relative; transform-style:preserve-3d; transition:transform 0.7s cubic-bezier(0.4,0,0.2,1); }
        .box-inner.flipped { transform:rotateY(180deg); }
        .box-front, .box-back { position:absolute; width:100%; height:100%; backface-visibility:hidden; border-radius:14px; display:flex; flex-direction:column; align-items:center; justify-content:center; }
        .box-front { background:#12182B; border:2px solid rgba(255,184,0,0.25); transition:border-color 0.2s,box-shadow 0.2s; }
        .box-wrap:not(.disabled):hover .box-front { border-color:rgba(255,184,0,0.6); box-shadow:0 0 20px rgba(255,184,0,0.15); }
        .box-front-icon { font-size:36px; margin-bottom:6px; }
        .box-front-text { font-size:11px; color:rgba(255,255,255,0.3); letter-spacing:2px; text-transform:uppercase; }
        .box-back { transform:rotateY(180deg); border:2px solid transparent; }
        .box-back.chosen { border-color:#FFB800 !important; box-shadow:0 0 24px rgba(255,184,0,0.5) !important; }
        .box-prize-emoji { font-size:38px; margin-bottom:6px; }
        .box-prize-label { font-size:12px; font-weight:700; text-align:center; line-height:1.3; }
 
        /* ── RESULT ── */
        .result-section { display:none; text-align:center; max-width:500px; }
        .result-section.show { display:block; }
        .result-card { background:#12182B; border:2px solid #FFB800; border-radius:20px; padding:40px 32px; margin-bottom:20px; box-shadow:0 0 40px rgba(255,184,0,0.2); }
        .result-emoji { font-size:64px; margin-bottom:14px; display:block; }
        .result-prize { font-size:28px; font-weight:700; margin-bottom:16px; }
        .result-label { font-size:14px; color:rgba(255,255,255,0.45); margin-bottom:20px; }
        .result-code-label { font-size:12px; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.3); margin-bottom:8px; }
        .result-code { font-size:26px; font-weight:700; letter-spacing:4px; color:#FFB800; background:rgba(255,184,0,0.08); border:1px solid rgba(255,184,0,0.3); border-radius:10px; padding:12px 24px; display:inline-block; margin-bottom:20px; cursor:pointer; transition:background 0.2s; }
        .result-code:hover { background:rgba(255,184,0,0.15); }
        .result-hint { font-size:13px; color:rgba(255,255,255,0.35); }
        .btn-shop { display:inline-block; background:#FFB800; color:#0B0F1A; padding:12px 32px; border-radius:10px; text-decoration:none; font-size:14px; font-weight:700; margin-top:16px; transition:background 0.2s; }
        .btn-shop:hover { background:#FFC933; }
        .copied-msg { display:none; color:#4CAF50; font-size:13px; margin-top:8px; font-weight:600; }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="../shop.php">Shop</a>
        <a href="inbox.php">📬 Inbox</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>
 
<div class="wrap">
 
    <?php if (!$game_active): ?>
    <!-- ── KEY ENTRY FORM ── -->
    <div class="key-card">
        <span class="key-icon">🎲</span>
        <div class="key-title">Lucky Box</div>
        <p class="key-sub">Enter the exclusive key sent to your inbox to unlock your Lucky Box and win a discount code!</p>
        <?php if ($error): ?>
            <div class="key-error">⚠ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="key_code" class="key-input" placeholder="Enter your key (e.g. RF-XXXXXXXX)" required autocomplete="off" style="text-transform:uppercase;">
            <button type="submit" class="btn-enter">🔓 Unlock Lucky Box</button>
        </form>
    </div>
 
    <?php else: ?>
    <!-- ── GAME GRID ── -->
    <div class="game-section" id="gameSection">
        <h1 class="game-title">🎲 Lucky Box</h1>
        <p class="game-sub">Pick one box to reveal your prize!</p>
        <div class="grid" id="grid">
            <?php for ($i = 0; $i < 9; $i++): ?>
            <div class="box-wrap" id="wrap-<?php echo $i; ?>" onclick="pickBox(<?php echo $i; ?>)">
                <div class="box-inner" id="box-<?php echo $i; ?>">
                    <div class="box-front">
                        <span class="box-front-icon">🎁</span>
                        <span class="box-front-text">TAP</span>
                    </div>
                    <div class="box-back" id="back-<?php echo $i; ?>">
                        <span class="box-prize-emoji" id="emoji-<?php echo $i; ?>"></span>
                        <span class="box-prize-label" id="label-<?php echo $i; ?>"></span>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
 
    <!-- ── RESULT (shown after pick) ── -->
    <div class="result-section" id="resultSection">
        <div class="result-card">
            <span class="result-emoji" id="resultEmoji"></span>
            <div class="result-prize" id="resultPrize"></div>
            <p class="result-label">Your discount code:</p>
            <div class="result-code-label">PROMO CODE</div>
            <div class="result-code" id="resultCode" onclick="copyCode()"></div>
            <div class="copied-msg" id="copiedMsg">✓ Copied to clipboard!</div>
            <p class="result-hint">Use this code at checkout to claim your discount.</p>
        </div>
        <a href="../shop.php" class="btn-shop">🛒 Go Shopping Now →</a>
    </div>
    <?php endif; ?>
 
</div>
 
<script>
var prizes  = <?php echo $prizes_json; ?>;
var picked  = false;
var wonCode = '';
 
function pickBox(index) {
    if (picked || prizes.length === 0) return;
    picked = true;
 
    // Disable all boxes
    document.querySelectorAll('.box-wrap').forEach(function(w){ w.classList.add('disabled'); });
 
    // Fetch result from server
    fetch('game.php?action=pick&box=' + index)
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); return; }
        wonCode = data.promo_code;
 
        // Reveal all boxes with staggered delay
        data.prizes.forEach(function(p, i) {
            setTimeout(function() {
                // Set back face content
                document.getElementById('emoji-' + i).textContent = p.emoji;
                var lbl = document.getElementById('label-' + i);
                lbl.textContent = p.label;
                lbl.style.color = p.color;
                document.getElementById('back-' + i).style.background = p.bg;
                // Flip
                document.getElementById('box-' + i).classList.add('flipped');
                // Highlight chosen box
                if (i === data.chosen) {
                    setTimeout(function(){
                        document.getElementById('back-' + i).classList.add('chosen');
                    }, 400);
                }
            }, i * 120);
        });
 
        // Show result after all boxes flip
        setTimeout(function() {
            var p = data.prize;
            document.getElementById('resultEmoji').textContent = p.emoji;
            document.getElementById('resultPrize').textContent = p.label + ' Discount!';
            document.getElementById('resultPrize').style.color = p.color;
            document.getElementById('resultCode').textContent = data.promo_code;
            document.getElementById('gameSection').style.marginBottom = '32px';
            document.getElementById('resultSection').classList.add('show');
        }, 9 * 120 + 800);
    });
}
 
function copyCode() {
    navigator.clipboard.writeText(wonCode).then(function() {
        document.getElementById('copiedMsg').style.display = 'block';
        setTimeout(function(){ document.getElementById('copiedMsg').style.display = 'none'; }, 2000);
    });
}
</script>
 
<?php $base = '../'; include '../includes/footer.php'; ?>
</body>
</html>