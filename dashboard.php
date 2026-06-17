<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}
include '../includes/db_connect.php';
 
$msg = ''; $msg_type = 'success';
 
// ── HANDLE ALL ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
 
    // ADD PRODUCT
    if ($action === 'add_product') {
        $name  = trim($_POST['name']);
        $desc  = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $cat   = intval($_POST['category_id']);
        $stock = intval($_POST['stock']);
        if ($name && $price > 0) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, stock) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssdii", $name, $desc, $price, $cat, $stock);
            $stmt->execute() ? $msg = "✓ '$name' added!" : ($msg = "Error adding product." and $msg_type = 'error');
        } else { $msg = "Name and price are required."; $msg_type = 'error'; }
    }
 
    // UPDATE PRODUCT
    if ($action === 'update_product') {
        $id    = intval($_POST['id']);
        $name  = trim($_POST['name']);
        $desc  = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $cat   = intval($_POST['category_id']);
        $stock = intval($_POST['stock']);
        $stmt  = $conn->prepare("UPDATE products SET name=?,description=?,price=?,category_id=?,stock=? WHERE id=?");
        $stmt->bind_param("ssdiis", $name, $desc, $price, $cat, $stock, $id);
        $stmt->execute() ? $msg = "✓ Product updated!" : ($msg = "Error updating." and $msg_type='error');
    }
 
    // DELETE PRODUCT
    if ($action === 'delete_product') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM order_items WHERE product_id=$id");
        $conn->query("DELETE FROM cart WHERE product_id=$id");
        $conn->query("DELETE FROM products WHERE id=$id");
        $msg = "✓ Product deleted.";
    }
 
    // ADD PROMO
    if ($action === 'add_promo') {
        $code  = strtoupper(trim($_POST['code']));
        $type  = $_POST['discount_type'];
        $value = floatval($_POST['discount_value']);
        if ($code && $value > 0) {
            $stmt = $conn->prepare("INSERT INTO promo_codes (code, discount_type, discount_value) VALUES (?,?,?)");
            $stmt->bind_param("ssd", $code, $type, $value);
            $stmt->execute() ? $msg = "✓ Promo code '$code' added!" : ($msg = "Code already exists." and $msg_type='error');
        }
    }
 
    // TOGGLE PROMO
    if ($action === 'toggle_promo') {
        $id = intval($_POST['id']);
        $conn->query("UPDATE promo_codes SET is_active = !is_active WHERE id=$id");
        $msg = "✓ Promo code status toggled.";
    }
 
    // DELETE PROMO
    if ($action === 'delete_promo') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM promo_codes WHERE id=$id");
        $msg = "✓ Promo code deleted.";
    }
 
    // UPDATE ORDER STATUS
    if ($action === 'update_order_status') {
        $oid    = intval($_POST['order_id']);
        $status = $_POST['new_status'];
        $allowed = ['pending','success','cancelled'];
        if (in_array($status, $allowed)) {
            $conn->query("UPDATE orders SET status='$status' WHERE id=$oid");
            $msg = "✓ Order #$oid marked as " . ucfirst($status) . '!';
        }
    }
 
    // UPLOAD QR CODE
    if ($action === 'upload_qr') {
        $dir = '../uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (isset($_FILES['qr_file']) && $_FILES['qr_file']['error'] === 0) {
            $types = ['image/jpeg','image/png','image/gif','image/webp'];
            if (in_array($_FILES['qr_file']['type'], $types)) {
                move_uploaded_file($_FILES['qr_file']['tmp_name'], $dir.'qr_bank.png');
                $msg = '✓ QR code uploaded!';
            } else { $msg = 'Please upload an image (PNG/JPG/GIF).'; $msg_type='error'; }
        } else { $msg = 'No file selected.'; $msg_type='error'; }
    }
 
    // SEND KEYS TO ALL USERS AT ONCE
    if ($action === 'send_keys_all') {
        $all_u = $conn->query("SELECT id FROM users");
        $sent  = 0;
        while ($u = $all_u->fetch_assoc()) {
            $key = 'RF-' . strtoupper(substr(md5(uniqid(rand(),true)), 0, 8));
            $conn->query("INSERT INTO game_keys (key_code) VALUES ('$key')");
            $subj = '🎲 Your Lucky Box Key is Here!';
            $body = "Your exclusive Lucky Box key: $key\n\nClick 🎲 Lucky Box in the navigation bar to play!\n\nPick a mystery box → win a discount code. Good luck!";
            $uid  = $u['id'];
            $s = $conn->prepare("INSERT INTO messages (sender_id, subject, body, is_global, recipient_id) VALUES (0,?,?,0,?)");
            $s->bind_param('ssi', $subj, $body, $uid);
            $s->execute();
            $sent++;
        }
        $msg = "✓ Keys generated and sent to " . $sent . " users!";
    }
 
    // GENERATE GAME KEY
    if ($action === 'generate_key') {
        $key = 'RF-' . strtoupper(substr(md5(uniqid(rand(),true)), 0, 8));
        $conn->query("INSERT INTO game_keys (key_code) VALUES ('$key')");
        $msg = "✓ Key generated: <strong style='letter-spacing:2px;'>$key</strong> — send it to a user via the Messages tab!";
    }
 
    // SEND KEY TO SPECIFIC USER
    if ($action === 'send_key_msg') {
        $key_code  = trim($_POST['key_code']);
        $target_id = intval($_POST['user_id']);
        $subject   = '🎲 You received a Lucky Box Key!';
        $body      = "Your exclusive Lucky Box key is: $key_code\n\nVisit the game page and enter your key to play!\n\n🔗 localhost/ecommerce/pages/game.php\n\nPick a mystery box and win a discount code. Good luck!";
        $s = $conn->prepare("INSERT INTO messages (sender_id, subject, body, is_global, recipient_id) VALUES (0,?,?,0,?)");
        $s->bind_param('ssi', $subject, $body, $target_id);
        $s->execute() ? $msg = '✓ Key sent to user!' : ($msg='Error sending.' and $msg_type='error');
    }
 
    // SEND MESSAGE TO ALL USERS
    if ($action === 'send_message') {
        $subject = trim($_POST['subject']);
        $body    = trim($_POST['body']);
        if ($subject && $body) {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, subject, body, is_global) VALUES (0, ?, ?, 1)");
            $stmt->bind_param('ss', $subject, $body);
            $stmt->execute() ? $msg = '✓ Message sent to all users!' : ($msg='Error.' and $msg_type='error');
        } else { $msg='Subject and message are required.'; $msg_type='error'; }
    }
 
    // DELETE MESSAGE
    if ($action === 'delete_message') {
        $mid = intval($_POST['message_id']);
        $conn->query("DELETE FROM message_reads WHERE message_id=$mid");
        $conn->query("DELETE FROM messages WHERE id=$mid");
        $msg = '✓ Message deleted.';
    }
 
    // SAVE BANK DETAILS
    if ($action === 'save_bank') {
        $bank_data = [
            'bank_name' => trim($_POST['bank_name']),
            'bank_code' => trim($_POST['bank_code']),
            'account'   => trim($_POST['account']),
            'holder'    => trim($_POST['holder']),
            'note'      => trim($_POST['note']),
        ];
        file_put_contents('../includes/bank.json', json_encode($bank_data, JSON_PRETTY_PRINT));
        $msg = '✓ Bank details saved!';
    }
}
 
// GET EDIT DATA
$edit_product = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $r = $conn->query("SELECT * FROM products WHERE id=$eid");
    $edit_product = $r->fetch_assoc();
}
 
// ── FETCH DATA FOR DISPLAY ──
$products   = $conn->query("SELECT * FROM products ORDER BY category_id ASC, price ASC");
$promos     = $conn->query("SELECT * FROM promo_codes ORDER BY id DESC");
$orders     = $conn->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 15");
$stats_prod = $conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$stats_ord  = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$stats_user = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$stats_rev  = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders")->fetch_assoc()['s'];
$cat_names  = [1=>'Legendary', 2=>'Mythic', 3=>'Divine'];
$cat_colors = [1=>'#FFD700',   2=>'#E53535', 3=>'#FFFFFF'];
 
$active_tab = $_GET['tab'] ?? 'products';
// Fetch sent messages
// Fetch game keys
$game_keys = $conn->query("SELECT gk.*, u.username FROM game_keys gk LEFT JOIN users u ON u.id=gk.used_by_user ORDER BY gk.created_at DESC");
// Fetch all users for dropdown
$all_users = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
$all_msgs = $conn->query("SELECT m.*, (SELECT COUNT(*) FROM message_reads WHERE message_id=m.id) as read_count FROM messages m ORDER BY m.created_at DESC");
// Load bank details
$bank_file = '../includes/bank.json';
$bank_cfg = file_exists($bank_file)
    ? json_decode(file_get_contents($bank_file), true)
    : ['bank_name'=>'','account'=>'','holder'=>'','note'=>''];
$qr_exists = file_exists('../uploads/qr_bank.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#080C17; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; }
 
        /* ── TOP NAV ── */
        .admin-nav { background:#0D1224; border-bottom:2px solid rgba(255,184,0,0.25); padding:14px 32px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; }
        .admin-nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:18px; text-decoration:none; }
        .admin-nav-logo span { font-size:11px; letter-spacing:2px; color:rgba(255,255,255,0.3); margin-left:10px; text-transform:uppercase; font-family:'Exo 2',sans-serif; }
        .admin-nav-links a { color:rgba(255,255,255,0.5); text-decoration:none; margin-left:20px; font-size:13px; font-weight:600; transition:color 0.2s; }
        .admin-nav-links a:hover { color:#FFB800; }
        .admin-nav-links .btn-logout { background:rgba(220,53,69,0.15); color:#ff6b6b; border:1px solid rgba(220,53,69,0.3); padding:6px 14px; border-radius:6px; }
        .admin-nav-links .btn-logout:hover { background:rgba(220,53,69,0.3); }
 
        /* ── WRAP ── */
        .wrap { max-width:1300px; margin:0 auto; padding:30px 24px 60px; }
 
        /* ── FLASH MSG ── */
        .flash { padding:12px 18px; border-radius:8px; margin-bottom:24px; font-size:14px; font-weight:600; }
        .flash.success { background:rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.3); color:#4CAF50; }
        .flash.error   { background:rgba(220,53,69,0.12);  border:1px solid rgba(220,53,69,0.3);  color:#ff6b6b; }
 
        /* ── STATS ── */
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:32px; }
        .stat-card { background:#0D1224; border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:20px 22px; }
        .stat-val { font-size:30px; font-weight:700; color:#FFB800; font-family:'Cinzel Decorative',serif; }
        .stat-label { font-size:12px; color:rgba(255,255,255,0.35); margin-top:4px; letter-spacing:0.5px; }
 
        /* ── TABS ── */
        .tabs { display:flex; gap:6px; margin-bottom:24px; border-bottom:1px solid rgba(255,255,255,0.07); padding-bottom:0; }
        .tab-btn { padding:10px 22px; background:transparent; border:none; border-bottom:2px solid transparent; color:rgba(255,255,255,0.4); font-family:'Exo 2',sans-serif; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; margin-bottom:-1px; }
        .tab-btn:hover { color:#FFB800; }
        .tab-btn.active { color:#FFB800; border-bottom-color:#FFB800; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
 
        /* ── PANELS ── */
        .panel { background:#0D1224; border:1px solid rgba(255,255,255,0.07); border-radius:14px; padding:24px; margin-bottom:24px; }
        .panel-title { font-family:'Cinzel Decorative',serif; font-size:15px; color:#FFB800; margin-bottom:20px; }
 
        /* ── TABLE ── */
        .data-table { width:100%; border-collapse:collapse; }
        .data-table th { text-align:left; padding:10px 12px; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,0.3); border-bottom:1px solid rgba(255,255,255,0.07); white-space:nowrap; }
        .data-table td { padding:12px 12px; border-bottom:1px solid rgba(255,255,255,0.04); font-size:14px; vertical-align:middle; }
        .data-table tr:hover td { background:rgba(255,255,255,0.02); }
        .data-table tr:last-child td { border-bottom:none; }
        .cat-pill { display:inline-block; padding:2px 10px; border-radius:12px; font-size:11px; font-weight:700; letter-spacing:0.5px; }
        .stock-warn { color:#ff4444; font-weight:700; }
        .stock-ok   { color:#4CAF50; }
 
        /* ── ACTION BUTTONS ── */
        .btn-edit   { background:rgba(255,184,0,0.12); color:#FFB800; border:1px solid rgba(255,184,0,0.3); padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; transition:background 0.2s; }
        .btn-edit:hover { background:rgba(255,184,0,0.25); }
        .btn-del    { background:rgba(220,53,69,0.12); color:#ff6b6b; border:1px solid rgba(220,53,69,0.3); padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; font-family:'Exo 2',sans-serif; transition:background 0.2s; }
        .btn-del:hover  { background:rgba(220,53,69,0.25); }
        .btn-toggle { background:rgba(255,255,255,0.07); color:rgba(255,255,255,0.5); border:1px solid rgba(255,255,255,0.15); padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; font-family:'Exo 2',sans-serif; transition:background 0.2s; }
 
        /* ── FORMS ── */
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
        .form-grid-wide { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:14px; align-items:end; }
        .field label { display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.45); margin-bottom:6px; letter-spacing:0.5px; }
        .field input, .field select, .field textarea { width:100%; background:#080C17; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; padding:10px 12px; font-family:'Exo 2',sans-serif; font-size:14px; transition:border-color 0.2s; }
        .field input:focus,.field select:focus,.field textarea:focus { outline:none; border-color:#FFB800; }
        .field select option { background:#12182B; }
        .field textarea { resize:vertical; min-height:60px; }
        .btn-submit { background:#FFB800; color:#0B0F1A; border:none; padding:10px 24px; border-radius:8px; font-family:'Exo 2',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:background 0.2s; white-space:nowrap; }
        .btn-submit:hover { background:#FFC933; }
        .btn-cancel { background:rgba(255,255,255,0.07); color:rgba(255,255,255,0.5); border:1px solid rgba(255,255,255,0.12); padding:10px 18px; border-radius:8px; font-family:'Exo 2',sans-serif; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
 
        .active-badge   { background:rgba(40,167,69,0.15); color:#4CAF50; border:1px solid rgba(40,167,69,0.3); padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
        .inactive-badge { background:rgba(255,255,255,0.07); color:rgba(255,255,255,0.3); border:1px solid rgba(255,255,255,0.12); padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
 
        .section-divider { border:none; border-top:1px solid rgba(255,255,255,0.07); margin:28px 0; }
        @media(max-width:900px){ .stats{grid-template-columns:repeat(2,1fr);} .form-grid-wide{grid-template-columns:1fr 1fr;} }
    </style>
</head>
<body>
 
<!-- ── Admin Nav ── -->
<div class="admin-nav">
    <a href="dashboard.php" class="admin-nav-logo">Ro-Fruit <span>Admin Panel</span></a>
    <div class="admin-nav-links">
        <a href="../index.php" target="_blank">View Store ↗</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>
 
<div class="wrap">
 
    <?php if ($msg): ?>
        <div class="flash <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
 
    <!-- ── Stats ── -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-val"><?php echo $stats_prod; ?></div>
            <div class="stat-label">Total Fruits</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $stats_ord; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $stats_user; ?></div>
            <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="font-size:22px;">NT$<?php echo number_format($stats_rev, 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
 
    <!-- ── Tabs ── -->
    <div class="tabs">
        <button class="tab-btn <?php echo $active_tab=='products'?'active':''; ?>" onclick="switchTab('products')">📦 Products</button>
        <button class="tab-btn <?php echo $active_tab=='promos'?'active':''; ?>"   onclick="switchTab('promos')">🎟️ Promo Codes</button>
        <button class="tab-btn <?php echo $active_tab=='orders'?'active':''; ?>"   onclick="switchTab('orders')">📋 Recent Orders</button>
        <button class="tab-btn <?php echo $active_tab=='settings'?'active':''; ?>" onclick="switchTab('settings')">⚙️ Settings</button>
        <button class="tab-btn <?php echo $active_tab==='messages'?'active':''; ?>" onclick="switchTab('messages')">📧 Messages</button>
        <button class="tab-btn <?php echo $active_tab==='gamekeys'?'active':''; ?>" onclick="switchTab('gamekeys')">🎲 Game Keys</button>
    </div>
 
    <!-- ══════════════════════════════════
         TAB 1: PRODUCTS
    ══════════════════════════════════ -->
    <div class="tab-content <?php echo $active_tab=='products'?'active':''; ?>" id="tab-products">
 
        <!-- Edit form (shows when editing) -->
        <?php if ($edit_product): ?>
        <div class="panel" style="border-color:rgba(255,184,0,0.3);">
            <div class="panel-title">✏️ Edit Product — <?php echo htmlspecialchars($edit_product['name']); ?></div>
            <form method="POST" action="dashboard.php?tab=products">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                <div class="form-grid">
                    <div class="field"><label>Name *</label><input type="text" name="name" required value="<?php echo htmlspecialchars($edit_product['name']); ?>"></div>
                    <div class="field"><label>Category</label>
                        <select name="category_id">
                            <option value="1" <?php echo $edit_product['category_id']==1?'selected':''; ?>>🌟 Legendary</option>
                            <option value="2" <?php echo $edit_product['category_id']==2?'selected':''; ?>>🔮 Mythic</option>
                            <option value="3" <?php echo $edit_product['category_id']==3?'selected':''; ?>>👑 Divine</option>
                        </select>
                    </div>
                    <div class="field"><label>Price (NT$) *</label><input type="number" name="price" min="1" required value="<?php echo $edit_product['price']; ?>"></div>
                    <div class="field"><label>Stock</label><input type="number" name="stock" min="0" value="<?php echo $edit_product['stock']; ?>"></div>
                    <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($edit_product['description']); ?></textarea></div>
                </div>
                <div style="display:flex;gap:10px;margin-top:16px;">
                    <button type="submit" class="btn-submit">💾 Save Changes</button>
                    <a href="dashboard.php?tab=products" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
 
        <!-- Add new product -->
        <div class="panel">
            <div class="panel-title">➕ Add New Fruit</div>
            <form method="POST" action="dashboard.php?tab=products">
                <input type="hidden" name="action" value="add_product">
                <div class="form-grid">
                    <div class="field"><label>Name *</label><input type="text" name="name" placeholder="e.g. Ice Fruit" required></div>
                    <div class="field"><label>Category</label>
                        <select name="category_id">
                            <option value="1">🌟 Legendary</option>
                            <option value="2">🔮 Mythic</option>
                            <option value="3">👑 Divine</option>
                        </select>
                    </div>
                    <div class="field"><label>Price (NT$) *</label><input type="number" name="price" min="1" placeholder="e.g. 99"></div>
                    <div class="field"><label>Stock</label><input type="number" name="stock" min="0" value="10"></div>
                    <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description" placeholder="Describe the fruit's powers..."></textarea></div>
                </div>
                <button type="submit" class="btn-submit" style="margin-top:16px;">➕ Add Fruit</button>
            </form>
        </div>
 
        <!-- Products table -->
        <div class="panel">
            <div class="panel-title">📦 All Fruits (<?php echo $stats_prod; ?>)</div>
            <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr>
                    <th>#</th><th>Name</th><th>Category</th>
                    <th>Price</th><th>Stock</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php $products->data_seek(0); while ($pr = $products->fetch_assoc()):
                    $cid = $pr['category_id'];
                    $clr = $cat_colors[$cid] ?? '#FFB800';
                    $cnm = $cat_names[$cid] ?? 'Unknown';
                ?>
                <tr>
                    <td style="color:rgba(255,255,255,0.3);font-size:12px;"><?php echo $pr['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($pr['name']); ?></strong></td>
                    <td><span class="cat-pill" style="color:<?php echo $clr; ?>;background:<?php echo $clr; ?>18;border:1px solid <?php echo $clr; ?>33;"><?php echo $cnm; ?></span></td>
                    <td>NT$<?php echo number_format($pr['price'], 0); ?></td>
                    <td class="<?php echo $pr['stock']<=3&&$pr['stock']>0?'stock-warn':($pr['stock']==0?'stock-warn':'stock-ok'); ?>">
                        <?php echo $pr['stock']==0 ? '✕ Out' : $pr['stock']; ?>
                    </td>
                    <td style="display:flex;gap:8px;align-items:center;">
                        <a href="dashboard.php?edit=<?php echo $pr['id']; ?>&tab=products" class="btn-edit">✏️ Edit</a>
                        <form method="POST" action="dashboard.php?tab=products" style="display:inline;" onsubmit="return confirm('Delete <?php echo addslashes($pr['name']); ?>?')">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="id" value="<?php echo $pr['id']; ?>">
                            <button type="submit" class="btn-del">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
 
    <!-- ══════════════════════════════════
         TAB 2: PROMO CODES
    ══════════════════════════════════ -->
    <div class="tab-content <?php echo $active_tab=='promos'?'active':''; ?>" id="tab-promos">
        <div class="panel">
            <div class="panel-title">➕ Add New Promo Code</div>
            <form method="POST" action="dashboard.php?tab=promos">
                <input type="hidden" name="action" value="add_promo">
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:14px;align-items:end;">
                    <div class="field"><label>Promo Code *</label><input type="text" name="code" placeholder="e.g. SUMMER30" required style="text-transform:uppercase;"></div>
                    <div class="field"><label>Type</label>
                        <select name="discount_type">
                            <option value="percent">% Percentage</option>
                            <option value="fixed">NT$ Fixed Amount</option>
                        </select>
                    </div>
                    <div class="field"><label>Value *</label><input type="number" name="discount_value" min="1" placeholder="e.g. 20" required></div>
                    <button type="submit" class="btn-submit">➕ Add Code</button>
                </div>
            </form>
        </div>
 
        <div class="panel">
            <div class="panel-title">🎟️ All Promo Codes</div>
            <table class="data-table">
                <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $promos->data_seek(0); while ($pc = $promos->fetch_assoc()): ?>
                <tr>
                    <td><strong style="letter-spacing:1px;"><?php echo htmlspecialchars($pc['code']); ?></strong></td>
                    <td><?php echo $pc['discount_type'] === 'percent' ? 'Percentage' : 'Fixed NT$'; ?></td>
                    <td style="color:#FFB800;font-weight:700;">
                        <?php echo $pc['discount_type']==='percent' ? $pc['discount_value'].'%' : 'NT$'.number_format($pc['discount_value'],0); ?>
                    </td>
                    <td>
                        <span class="<?php echo $pc['is_active'] ? 'active-badge' : 'inactive-badge'; ?>">
                            <?php echo $pc['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td style="display:flex;gap:8px;">
                        <form method="POST" action="dashboard.php?tab=promos" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_promo">
                            <input type="hidden" name="id" value="<?php echo $pc['id']; ?>">
                            <button type="submit" class="btn-toggle"><?php echo $pc['is_active']?'Disable':'Enable'; ?></button>
                        </form>
                        <form method="POST" action="dashboard.php?tab=promos" style="display:inline;" onsubmit="return confirm('Delete this code?')">
                            <input type="hidden" name="action" value="delete_promo">
                            <input type="hidden" name="id" value="<?php echo $pc['id']; ?>">
                            <button type="submit" class="btn-del">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
 
    <!-- ══════════════════════════════════
         TAB 3: RECENT ORDERS
    ══════════════════════════════════ -->
    <div class="tab-content <?php echo $active_tab=='orders'?'active':''; ?>" id="tab-orders">
        <div class="panel">
            <div class="panel-title">📋 Recent Orders (last 15)</div>
            <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order #</th><th>User</th><th>Total</th><th>Roblox Account</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $orders->data_seek(0); while ($ord = $orders->fetch_assoc()):
                    $addr_parts = explode(' | ', $ord['address']);
                    $roblox = '';
                    foreach ($addr_parts as $pt) {
                        if (str_starts_with($pt, 'Roblox: ')) $roblox = substr($pt, 8);
                    }
                    $s = $ord['status'];
                    $s_style = $s==='success'?'active-badge':($s==='cancelled'?'inactive-badge':'');
                    $s_style_pending = $s==='pending'?'style="background:rgba(255,184,0,0.15);color:#FFB800;border:1px solid rgba(255,184,0,0.3);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;"':'';
                ?>
                <tr>
                    <td style="color:#FFB800;font-weight:700;">#<?php echo $ord['id']; ?></td>
                    <td><?php echo htmlspecialchars($ord['username'] ?? 'Unknown'); ?></td>
                    <td style="font-weight:700;">NT$<?php echo number_format($ord['total'],0); ?></td>
                    <td style="color:rgba(255,255,255,0.6);"><?php echo htmlspecialchars($roblox ?: '-'); ?></td>
                    <td>
                        <?php if ($s === 'pending'): ?>
                            <span style="background:rgba(255,184,0,0.15);color:#FFB800;border:1px solid rgba(255,184,0,0.3);padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">⏳ Pending</span>
                        <?php elseif ($s === 'success'): ?>
                            <span class="active-badge">✓ Paid</span>
                        <?php else: ?>
                            <span class="inactive-badge">✕ Cancelled</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:rgba(255,255,255,0.35);font-size:13px;"><?php echo date('M j, Y', strtotime($ord['created_at'])); ?></td>
                    <td>
                        <form method="POST" action="dashboard.php?tab=orders" style="display:flex;gap:6px;">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                            <?php if ($s !== 'success'): ?>
                            <button type="submit" name="new_status" value="success"
                                style="background:rgba(40,167,69,0.15);color:#4CAF50;border:1px solid rgba(40,167,69,0.3);padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;"
                                onclick="return confirm('Mark order #<?php echo $ord['id']; ?> as PAID?')">✓ Mark Paid</button>
                            <?php endif; ?>
                            <?php if ($s !== 'cancelled'): ?>
                            <button type="submit" name="new_status" value="cancelled"
                                style="background:rgba(220,53,69,0.1);color:#ff6b6b;border:1px solid rgba(220,53,69,0.25);padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;"
                                onclick="return confirm('Cancel order #<?php echo $ord['id']; ?>?')">✕ Cancel</button>
                            <?php endif; ?>
                            <?php if ($s !== 'pending'): ?>
                            <button type="submit" name="new_status" value="pending"
                                style="background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.4);border:1px solid rgba(255,255,255,0.12);padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;">Reset</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
 
</div>
 
<script>
function switchTab(name) {
    document.querySelectorAll('.tab-content').forEach(function(el){ el.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(el){ el.classList.remove('active'); });
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
    history.replaceState(null,'','dashboard.php?tab='+name);
}
// Auto-uppercase promo code input
var codeInput = document.querySelector('input[name="code"]');
if (codeInput) codeInput.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
</script>
 
    <!-- SETTINGS TAB -->
    <div class="tab-content <?php echo $active_tab==='settings'?'active':''; ?>" id="tab-settings">
 
        <!-- Bank Details -->
        <div class="panel">
            <div class="panel-title">🏦 Bank Transfer Details</div>
            <form method="POST" action="dashboard.php?tab=settings">
                <input type="hidden" name="action" value="save_bank">
                <div class="form-grid">
                    <div class="field"><label>Bank Name</label><input type="text" name="bank_name" value="<?php echo htmlspecialchars($bank_cfg['bank_name']); ?>" placeholder="e.g. 台灣銀行 / LINE Bank"></div>
                    <div class="field"><label>Bank Code / 局號</label><input type="text" name="bank_code" value="<?php echo htmlspecialchars($bank_cfg['bank_code'] ?? ''); ?>" placeholder="e.g. 700 or 0001234"></div>
                    <div class="field"><label>Account Number</label><input type="text" name="account" value="<?php echo htmlspecialchars($bank_cfg['account']); ?>" placeholder="e.g. 0123456789"></div>
                    <div class="field"><label>Account Holder</label><input type="text" name="holder" value="<?php echo htmlspecialchars($bank_cfg['holder']); ?>" placeholder="Your full name"></div>
                    <div class="field" style="grid-column:1/-1"><label>Payment Note (shown to buyer)</label><textarea name="note" style="min-height:50px;"><?php echo htmlspecialchars($bank_cfg['note']); ?></textarea></div>
                </div>
                <button type="submit" class="btn-submit" style="margin-top:16px;">💾 Save Bank Details</button>
            </form>
        </div>
 
        <!-- QR Code Upload -->
        <div class="panel">
            <div class="panel-title">📷 Bank QR Code</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">
                <div>
                    <form method="POST" action="dashboard.php?tab=settings" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_qr">
                        <div class="field"><label>Upload QR Code Image (PNG/JPG)</label>
                            <input type="file" name="qr_file" accept="image/*" style="padding:8px;">
                        </div>
                        <button type="submit" class="btn-submit" style="margin-top:8px;">⬆️ Upload QR Code</button>
                    </form>
                    <p style="font-size:12px;color:rgba(255,255,255,0.3);margin-top:10px;">This QR code will be shown on the checkout page so buyers can scan and pay directly.</p>
                </div>
                <div style="text-align:center;">
                    <?php if ($qr_exists): ?>
                        <p style="font-size:12px;color:rgba(255,255,255,0.35);margin-bottom:10px;">Current QR Code:</p>
                        <img src="../uploads/qr_bank.png" alt="QR Code" style="max-width:180px;border-radius:10px;border:2px solid rgba(255,184,0,0.3);">
                    <?php else: ?>
                        <div style="width:180px;height:180px;border:2px dashed rgba(255,255,255,0.12);border-radius:10px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.2);font-size:13px;margin:0 auto;">No QR uploaded yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
 
 
    <!-- MESSAGES TAB -->
    <div class="tab-content <?php echo $active_tab==='messages'?'active':''; ?>" id="tab-messages">
 
        <!-- Compose -->
        <div class="panel">
            <div class="panel-title">📧 Send Message to All Users</div>
            <form method="POST" action="dashboard.php?tab=messages">
                <input type="hidden" name="action" value="send_message">
                <div class="field" style="margin-bottom:14px;">
                    <label>Subject *</label>
                    <input type="text" name="subject" placeholder="e.g. New Fruit Available! 🔥" required>
                </div>
                <div class="field" style="margin-bottom:14px;">
                    <label>Message *</label>
                    <textarea name="body" rows="5" placeholder="Write your message here..." required style="min-height:120px;"></textarea>
                </div>
                <button type="submit" class="btn-submit">📤 Send to All Users</button>
            </form>
        </div>
 
        <!-- Sent messages list -->
        <div class="panel">
            <div class="panel-title">📬 Sent Messages</div>
            <?php if ($all_msgs->num_rows === 0): ?>
                <p style="color:rgba(255,255,255,0.3);font-size:14px;">No messages sent yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Subject</th><th>Sent To</th><th>Read By</th><th>Date</th><th>Delete</th></tr></thead>
                <tbody>
                <?php while ($m = $all_msgs->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($m['subject']); ?></strong></td>
                    <td><span class="active-badge">All Users</span></td>
                    <td style="color:#4CAF50;"><?php echo $m['read_count']; ?> users</td>
                    <td style="color:rgba(255,255,255,0.35);font-size:13px;"><?php echo date('M j, Y g:i A', strtotime($m['created_at'])); ?></td>
                    <td>
                        <form method="POST" action="dashboard.php?tab=messages" onsubmit="return confirm('Delete this message?')">
                            <input type="hidden" name="action" value="delete_message">
                            <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                            <button type="submit" class="btn-del">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
 
 
    <!-- GAME KEYS TAB -->
    <div class="tab-content <?php echo $active_tab==='gamekeys'?'active':''; ?>" id="tab-gamekeys">
 
        <!-- Generate + Send -->
        <div class="panel" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
 
            <!-- Generate Key -->
            <div>
                <div class="panel-title">🎲 Generate New Key</div>
                <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:16px;line-height:1.6;">Each key can only be used once. After generating, send it to a user via their inbox so they can play the Lucky Box game.</p>
                <form method="POST" action="dashboard.php?tab=gamekeys" style="margin-bottom:12px;">
                    <input type="hidden" name="action" value="generate_key">
                    <button type="submit" class="btn-submit" style="width:100%;">✨ Generate New Key</button>
                </form>
                <form method="POST" action="dashboard.php?tab=gamekeys" onsubmit="return confirm('Send a unique Lucky Box key to ALL registered users?')">
                    <input type="hidden" name="action" value="send_keys_all">
                    <button type="submit" style="width:100%;background:rgba(168,85,247,0.15);color:#A855F7;border:1px solid rgba(168,85,247,0.4);padding:10px;border-radius:8px;font-family:'Exo 2',sans-serif;font-size:13px;font-weight:700;cursor:pointer;">📤 Send Keys to ALL Users</button>
                </form>
            </div>
 
            <!-- Send Key to User -->
            <div>
                <div class="panel-title">📤 Send Key to User</div>
                <form method="POST" action="dashboard.php?tab=gamekeys">
                    <input type="hidden" name="action" value="send_key_msg">
                    <div class="field" style="margin-bottom:12px;">
                        <label>Key Code</label>
                        <input type="text" name="key_code" placeholder="RF-XXXXXXXX" required style="text-transform:uppercase;">
                    </div>
                    <div class="field" style="margin-bottom:12px;">
                        <label>Send to User</label>
                        <select name="user_id" required>
                            <option value="">— Select user —</option>
                            <?php $all_users->data_seek(0); while ($u = $all_users->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" style="width:100%;">📬 Send to Inbox</button>
                </form>
            </div>
        </div>
 
        <!-- Keys table -->
        <div class="panel">
            <div class="panel-title">🗝️ All Game Keys</div>
            <?php if ($game_keys->num_rows === 0): ?>
                <p style="color:rgba(255,255,255,0.3);font-size:14px;">No keys generated yet.</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Key Code</th><th>Status</th><th>Used By</th><th>Won Code</th><th>Date</th></tr></thead>
                <tbody>
                <?php $game_keys->data_seek(0); while ($gk = $game_keys->fetch_assoc()): ?>
                <tr>
                    <td><strong style="letter-spacing:1px;color:#FFB800;"><?php echo htmlspecialchars($gk['key_code']); ?></strong></td>
                    <td>
                        <?php if ($gk['is_used']): ?>
                            <span class="inactive-badge">✓ Used</span>
                        <?php else: ?>
                            <span class="active-badge">Available</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $gk['username'] ? htmlspecialchars($gk['username']) : '—'; ?></td>
                    <td style="color:#4CAF50;letter-spacing:1px;"><?php echo $gk['won_code'] ? htmlspecialchars($gk['won_code']) : '—'; ?></td>
                    <td style="color:rgba(255,255,255,0.35);font-size:13px;"><?php echo date('M j, Y', strtotime($gk['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
 
</body>
</html>