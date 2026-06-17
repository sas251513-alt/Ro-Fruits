<?php
session_start();
include '../includes/db_connect.php';
 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$uid = $_SESSION['user_id'];
 
// Mark a message as read
if (isset($_GET['mark_read'])) {
    $mid = intval($_GET['mark_read']);
    $check = $conn->query("SELECT id FROM message_reads WHERE message_id=$mid AND user_id=$uid");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO message_reads (message_id, user_id) VALUES ($mid, $uid)");
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}
 
// Fetch all global messages with read status
$msgs = $conn->query("
    SELECT m.*, IF(mr.id IS NOT NULL, 1, 0) as is_read
    FROM messages m
    LEFT JOIN message_reads mr ON mr.message_id = m.id AND mr.user_id = $uid
    WHERE m.is_global = 1 OR m.recipient_id = $uid
    ORDER BY m.created_at DESC
");
 
// Unread count
$uc = $conn->query("SELECT COUNT(*) c FROM messages m LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=$uid WHERE (m.is_global=1 OR m.recipient_id=$uid) AND mr.id IS NULL");
$unread_count = $uc->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; display:flex; flex-direction:column; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }
 
        .wrap { max-width:760px; margin:0 auto; padding:40px 24px 60px; flex:1; }
        .page-header { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
        h1 { font-family:'Cinzel Decorative',serif; font-size:24px; color:#FFB800; }
        .unread-pill { background:rgba(255,68,68,0.15); color:#ff4444; border:1px solid rgba(255,68,68,0.3); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
        .unread-pill.none { background:rgba(40,167,69,0.12); color:#4CAF50; border-color:rgba(40,167,69,0.3); }
 
        .empty { text-align:center; padding:80px 20px; color:rgba(255,255,255,0.25); font-size:15px; }
        .empty div { font-size:52px; margin-bottom:14px; }
 
        /* Message card */
        .msg-card { background:#12182B; border:1px solid rgba(255,255,255,0.07); border-radius:12px; margin-bottom:12px; overflow:hidden; transition:border-color 0.2s; cursor:pointer; }
        .msg-card.unread { border-left:3px solid #FFB800; background:#13192E; }
        .msg-card:hover { border-color:rgba(255,184,0,0.3); }
        .msg-card.unread:hover { border-color:#FFB800; }
 
        .msg-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .msg-left { display:flex; align-items:center; gap:12px; flex:1; min-width:0; }
        .msg-icon { font-size:22px; flex-shrink:0; }
        .msg-subject { font-weight:700; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .msg-card.unread .msg-subject { color:#FFB800; }
        .msg-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
        .msg-date { font-size:12px; color:rgba(255,255,255,0.3); }
        .unread-dot { width:8px; height:8px; border-radius:50%; background:#FFB800; flex-shrink:0; }
        .read-tick { color:rgba(255,255,255,0.2); font-size:13px; }
        .chevron { color:rgba(255,255,255,0.25); font-size:13px; transition:transform 0.25s; }
 
        /* Expanded body */
        .msg-body { display:none; padding:0 20px 20px; }
        .copy-key { display:inline-block; background:rgba(255,184,0,0.12); border:1px solid rgba(255,184,0,0.35); color:#FFB800; padding:4px 12px; border-radius:6px; font-weight:700; letter-spacing:1px; cursor:pointer; font-size:14px; transition:background 0.2s; }
        .copy-key:hover { background:rgba(255,184,0,0.25); }
        .key-copied { color:#4CAF50; font-size:12px; margin-left:8px; display:none; }
        .msg-body.open { display:block; }
        .msg-divider { border:none; border-top:1px solid rgba(255,255,255,0.07); margin-bottom:16px; }
        .msg-from { font-size:12px; color:rgba(255,255,255,0.3); margin-bottom:12px; }
        .msg-from strong { color:#FFB800; }
        .msg-text { font-size:14px; color:rgba(255,255,255,0.65); line-height:1.8; white-space:pre-wrap; }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="../shop.php">Shop</a>
        <a href="orders.php">📋 My Orders</a>
        <a href="cart.php">🛒 Cart</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>
 
<div class="wrap">
    <div class="page-header">
        <h1>📬 Inbox</h1>
        <?php if ($unread_count > 0): ?>
            <span class="unread-pill"><?php echo $unread_count; ?> unread</span>
        <?php else: ?>
            <span class="unread-pill none">All caught up ✓</span>
        <?php endif; ?>
    </div>
 
    <?php if ($msgs->num_rows === 0): ?>
        <div class="empty">
            <div>📭</div>
            <p>No messages yet.<br>Check back later for updates from Ro-Fruit!</p>
        </div>
    <?php else:
        while ($m = $msgs->fetch_assoc()):
            $is_unread = !$m['is_read'];
    ?>
        <div class="msg-card <?php echo $is_unread ? 'unread' : ''; ?>"
             id="msg-<?php echo $m['id']; ?>"
             onclick="toggleMsg(<?php echo $m['id']; ?>, <?php echo $is_unread ? 'true' : 'false'; ?>)">
            <div class="msg-header">
                <div class="msg-left">
                    <span class="msg-icon"><?php echo $is_unread ? '✉️' : '📨'; ?></span>
                    <span class="msg-subject"><?php echo htmlspecialchars($m['subject']); ?></span>
                </div>
                <div class="msg-right">
                    <span class="msg-date"><?php echo date('M j, g:i A', strtotime($m['created_at'])); ?></span>
                    <?php if ($is_unread): ?>
                        <span class="unread-dot"></span>
                    <?php else: ?>
                        <span class="read-tick">✓</span>
                    <?php endif; ?>
                    <span class="chevron" id="chev-<?php echo $m['id']; ?>">▼</span>
                </div>
            </div>
            <div class="msg-body" id="body-<?php echo $m['id']; ?>" onclick="event.stopPropagation()">
                <hr class="msg-divider">
                <div class="msg-from">From: <strong>Ro-Fruit Team</strong></div>
                <div class="msg-text" id="msgtext-<?php echo $m['id']; ?>"><?php
                    $raw = htmlspecialchars($m['body']);
                    // Make RF-XXXXXXXX keys clickable copy buttons
                    $raw = preg_replace('/\b(RF-[A-Z0-9]{8})\b/',
                        '<span class="copy-key" onclick="copyKey(this,event)" title="Click to copy">$1</span><span class="key-copied">✓ Copied!</span>',
                        $raw);
                    echo nl2br($raw);
                ?></div>
            </div>
        </div>
    <?php endwhile; endif; ?>
</div>
 
<script>
function copyKey(el, e) {
    e.stopPropagation();
    navigator.clipboard.writeText(el.textContent).then(function() {
        var notice = el.nextElementSibling;
        if (notice) { notice.style.display='inline'; setTimeout(function(){ notice.style.display='none'; }, 2000); }
    });
}
function toggleMsg(id, isUnread) {
    var body  = document.getElementById('body-' + id);
    var chev  = document.getElementById('chev-' + id);
    var card  = document.getElementById('msg-' + id);
    var open  = body.classList.toggle('open');
    chev.style.transform = open ? 'rotate(180deg)' : '';
 
    // Mark as read silently
    if (isUnread && open) {
        fetch('inbox.php?mark_read=' + id)
            .then(function() {
                card.classList.remove('unread');
                var dot = card.querySelector('.unread-dot');
                if (dot) { dot.outerHTML = '<span class="read-tick">✓</span>'; }
                var icon = card.querySelector('.msg-icon');
                if (icon) icon.textContent = '📨';
                // Update bell badge on this page
                var pill = document.querySelector('.unread-pill');
                if (pill) {
                    var count = parseInt(pill.textContent) - 1;
                    if (count <= 0) {
                        pill.textContent = 'All caught up ✓';
                        pill.className = 'unread-pill none';
                    } else {
                        pill.textContent = count + ' unread';
                    }
                }
            });
    }
}
</script>
 
<?php $base = '../'; include '../includes/footer.php'; ?>
</body>
</html>