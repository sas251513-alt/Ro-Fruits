<?php
session_start();
include '../includes/db_connect.php';
 
if (isset($_GET['add'])) {
    $product_id = intval($_GET['add']);
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
    // Get product name
    $pstmt = $conn->prepare('SELECT name FROM products WHERE id = ?');
    $pstmt->bind_param('i', $product_id);
    $pstmt->execute();
    $prow   = $pstmt->get_result()->fetch_assoc();
    $pname  = $prow['name'] ?? 'Item';
    $total_qty = array_sum($_SESSION['cart']);
    // AJAX call — return JSON, no redirect
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'name'=>$pname,'cart_count'=>$total_qty]);
        exit;
    }
    // Normal fallback redirect
    $_SESSION['last_added'] = $pname;
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../shop.php';
    header('Location: ' . $ref);
    exit;
}
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][intval($_GET['remove'])]);
    header('Location: cart.php');
    exit;
}
if (isset($_GET['update']) && isset($_GET['qty'])) {
    $pid = intval($_GET['update']);
    $qty = intval($_GET['qty']);
    if ($qty <= 0) unset($_SESSION['cart'][$pid]);
    else $_SESSION['cart'][$pid] = $qty;
    header('Location: cart.php');
    exit;
}
// Clear entire cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; text-shadow:0 0 15px rgba(255,184,0,0.4); }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }
 
        .wrap { max-width:850px; margin:40px auto; padding:0 24px; }
        h1 { font-family:'Cinzel Decorative',serif; font-size:26px; margin-bottom:28px; color:#FFB800; }
 
        .empty-msg { text-align:center; padding:70px 20px; color:rgba(255,255,255,0.3); }
        .empty-msg a { color:#FFB800; }
 
        table { width:100%; border-collapse:collapse; }
        thead tr { background:#12182B; }
        th { padding:13px 15px; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,0.4); font-weight:600; border-bottom:1px solid rgba(255,255,255,0.07); }
        th:first-child { text-align:left; } th:not(:first-child) { text-align:center; }
        td { padding:14px 15px; border-bottom:1px solid rgba(255,255,255,0.05); vertical-align:middle; }
        td:first-child { font-weight:600; font-size:15px; } td:not(:first-child) { text-align:center; }
 
        .qty-wrap { display:inline-flex; align-items:center; gap:10px; background:#12182B; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:4px 10px; }
        .qty-btn { display:inline-block; width:26px; height:26px; background:rgba(255,255,255,0.07); color:#fff; text-align:center; line-height:26px; border-radius:5px; text-decoration:none; font-size:18px; font-weight:700; transition:background 0.2s; }
        .qty-btn:hover { background:rgba(255,184,0,0.25); color:#FFB800; }
        .qty-num { font-size:16px; font-weight:700; min-width:22px; text-align:center; }
 
        .subtotal { color:#FFB800; font-weight:700; font-size:16px; }
        .remove-btn { color:rgba(255,80,80,0.7); text-decoration:none; font-size:18px; transition:color 0.2s; }
        .remove-btn:hover { color:#ff4444; }
 
        .summary { margin-top:24px; background:#12182B; border:1px solid rgba(255,184,0,0.2); border-radius:14px; padding:24px 28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; }
        .total-label { font-size:14px; color:rgba(255,255,255,0.4); margin-bottom:4px; }
        .total-amount { font-size:30px; font-weight:700; color:#FFB800; font-family:'Cinzel Decorative',serif; }
        .btn-checkout { display:inline-block; background:#FFB800; color:#0B0F1A; padding:13px 36px; border-radius:10px; text-decoration:none; font-size:15px; font-weight:700; letter-spacing:0.5px; transition:background 0.2s,transform 0.15s; }
        .btn-checkout:hover { background:#FFC933; transform:scale(1.03); }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="../shop.php">← Back to Shop</a>
        <?php if (isset($_SESSION['username'])): ?>
            <span style="color:#FFB800;">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>
 
<div class="wrap">
    <h1>🛒 Your Cart</h1>
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-msg">
            <p style="font-size:48px;margin-bottom:16px;">🛒</p>
            <p style="font-size:16px;margin-bottom:12px;">Your cart is empty.</p>
            <a href="../shop.php">Browse the Shop →</a>
        </div>
    <?php else:
        $ids      = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
        $result   = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
        $products = [];
        while ($p = $result->fetch_assoc()) $products[$p['id']] = $p;
        $total    = 0;
        $has_oos  = false; // tracks if any cart item is now out of stock
    ?>
    <table>
        <thead><tr><th>Product</th><th>Unit Price</th><th>Quantity</th><th>Subtotal</th><th>Remove</th></tr></thead>
        <tbody>
        <?php foreach ($_SESSION['cart'] as $pid => $qty):
            if (!isset($products[$pid])) continue;
            $p          = $products[$pid];
            $curr_stock = intval($p['stock']);
            $is_oos     = ($curr_stock == 0);
            if ($is_oos) $has_oos = true;
            $subtotal   = $p['price'] * $qty;
            if (!$is_oos) $total += $subtotal;
        ?>
            <tr style="<?php echo $is_oos?'opacity:0.45;':''; ?>">
                <td>
                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                    <?php if ($is_oos): ?>
                        <div style="color:#ff4444;font-size:12px;font-weight:700;margin-top:4px;">⚠ Out of Stock — someone bought the last one!</div>
                    <?php endif; ?>
                </td>
                <td>NT$<?php echo number_format($p['price'], 0); ?></td>
                <td>
                    <?php if ($is_oos): ?>
                        <span style="color:#ff4444;font-size:13px;font-weight:700;">Out of Stock</span>
                    <?php else: ?>
                    <div class="qty-wrap">
                        <a href="cart.php?update=<?php echo $pid; ?>&qty=<?php echo $qty-1; ?>" class="qty-btn">&#8722;</a>
                        <span class="qty-num"><?php echo $qty; ?></span>
                        <a href="cart.php?update=<?php echo $pid; ?>&qty=<?php echo $qty+1; ?>" class="qty-btn">&#43;</a>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="subtotal"><?php echo $is_oos ? '<span style="color:rgba(255,255,255,0.25);">—</span>' : 'NT$'.number_format($subtotal,0); ?></td>
                <td><a href="cart.php?remove=<?php echo $pid; ?>" class="remove-btn" title="Remove">&#10005;</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Remove All button -->
    <div style="text-align:right; margin-bottom:12px;">
        <a href="cart.php?clear=1"
           onclick="return confirm('Remove all items from your cart?')"
           style="color:rgba(255,80,80,0.6); font-size:13px; font-weight:600; text-decoration:none;"
           onmouseover="this.style.color='#ff4444'"
           onmouseout="this.style.color='rgba(255,80,80,0.6)'">
            🗑 Remove All Items
        </a>
    </div>
    <div class="summary">
        <div>
            <div class="total-label">Order Total</div>
            <div class="total-amount">NT$<?php echo number_format($total, 0); ?></div>
        </div>
        <?php if ($has_oos): ?>
            <div style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.3);color:#ff6b6b;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:12px;">
                ⚠ Some items in your cart are now out of stock. Please remove them before checking out.
            </div>
            <div class="btn-checkout" style="background:#333;color:rgba(255,255,255,0.3);cursor:not-allowed;display:block;text-align:center;pointer-events:none;">Proceed to Checkout →</div>
        <?php else: ?>
            <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>