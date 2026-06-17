<?php
session_start();
include '../includes/db_connect.php';
 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
 
// Fetch all orders for this user
$orders_result = $conn->query(
    "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }
 
        .wrap { max-width:860px; margin:40px auto; padding:0 24px 60px; }
        h1 { font-family:'Cinzel Decorative',serif; font-size:26px; margin-bottom:8px; color:#FFB800; }
        .subtitle { color:rgba(255,255,255,0.35); font-size:14px; margin-bottom:32px; }
 
        .empty-orders { text-align:center; padding:80px 20px; color:rgba(255,255,255,0.25); }
        .empty-orders p { font-size:16px; margin-top:16px; }
        .empty-orders a { color:#FFB800; text-decoration:none; }
 
        /* Order card */
        .order-card { background:#12182B; border:1px solid rgba(255,255,255,0.07); border-radius:14px; margin-bottom:20px; overflow:hidden; }
        .order-header { padding:18px 22px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-bottom:1px solid rgba(255,255,255,0.06); cursor:pointer; transition:background 0.2s; }
        .order-header:hover { background:rgba(255,255,255,0.03); }
 
        .order-id { font-family:'Cinzel Decorative',serif; font-size:15px; color:#FFB800; }
        .order-date { font-size:13px; color:rgba(255,255,255,0.35); margin-top:3px; }
        .order-right { text-align:right; }
        .order-total { font-size:20px; font-weight:700; color:#FFB800; }
        .order-status { display:inline-block; margin-top:4px; padding:3px 12px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; background:rgba(40,167,69,0.15); color:#4CAF50; border:1px solid rgba(40,167,69,0.3); }
 
        /* Order items (expandable) */
        .order-items { display:none; padding:0 22px 18px; }
        .order-items.open { display:block; }
        .order-items table { width:100%; border-collapse:collapse; margin-top:14px; }
        .order-items th { font-size:11px; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,0.3); padding:6px 8px; border-bottom:1px solid rgba(255,255,255,0.07); text-align:left; }
        .order-items th:not(:first-child) { text-align:center; }
        .order-items td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,0.04); font-size:14px; }
        .order-items td:not(:first-child) { text-align:center; }
 
        .order-meta { margin-top:12px; font-size:13px; color:rgba(255,255,255,0.35); line-height:1.7; }
        .order-meta strong { color:rgba(255,255,255,0.6); }
 
        .chevron { color:rgba(255,255,255,0.3); transition:transform 0.25s; font-size:14px; }
        .chevron.open { transform:rotate(180deg); }
 
        .toggle-hint { font-size:12px; color:rgba(255,255,255,0.25); margin-top:2px; }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="../shop.php">Shop</a>
        <span style="color:#FFB800;">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        <a href="cart.php">🛒 Cart</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>
 
<div class="wrap">
    <h1>📋 My Orders</h1>
    <p class="subtitle">All your past purchases — click an order to see the items</p>
 
    <?php if ($orders_result->num_rows == 0): ?>
        <div class="empty-orders">
            <div style="font-size:56px;">📦</div>
            <p>You haven't placed any orders yet.</p>
            <p style="margin-top:10px;"><a href="../shop.php">Browse the Shop →</a></p>
        </div>
    <?php else: ?>
        <?php while ($order = $orders_result->fetch_assoc()):
            // Fetch items for this order
            $oid = $order['id'];
            $items_result = $conn->query(
                "SELECT oi.*, p.name FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = $oid"
            );
 
            // Parse the address field for display
            $addr_raw = $order['address'];
            $roblox = ''; $note = ''; $invoice = '';
            foreach (explode(' | ', $addr_raw) as $part) {
                if (str_starts_with($part, 'Roblox: '))  $roblox  = substr($part, 8);
                if (str_starts_with($part, 'Note: '))    $note    = substr($part, 6);
                if (str_starts_with($part, 'Invoice '))  $invoice = $part;
            }
        ?>
        <div class="order-card">
            <div class="order-header" onclick="toggleOrder(<?php echo $oid; ?>)">
                <div>
                    <div class="order-id">Order #<?php echo $oid; ?></div>
                    <div class="order-date"><?php echo date('F j, Y — g:i A', strtotime($order['created_at'])); ?></div>
                    <div class="toggle-hint">Click to see items ▾</div>
                </div>
                <div class="order-right">
                    <div class="order-total">NT$<?php echo number_format($order['total'], 0); ?></div>
                    <div>
                        <?php
                        $st = $order['status'];
                        if ($st === 'success'):
                        ?><span style="background:rgba(40,167,69,0.15);color:#4CAF50;border:1px solid rgba(40,167,69,0.3);padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;">✓ Paid</span><?php
                        elseif ($st === 'cancelled'):
                        ?><span style="background:rgba(220,53,69,0.12);color:#ff6b6b;border:1px solid rgba(220,53,69,0.25);padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;">✕ Cancelled</span><?php
                        else:
                        ?><span style="background:rgba(255,184,0,0.12);color:#FFB800;border:1px solid rgba(255,184,0,0.25);padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;">⏳ Awaiting Payment</span><?php
                        endif; ?>
                    </div>
                </div>
            </div>
 
            <div class="order-items" id="order-<?php echo $oid; ?>">
                <table>
                    <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name'] ?? 'Deleted product'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>NT$<?php echo number_format($item['price'], 0); ?></td>
                            <td>NT$<?php echo number_format($item['price'] * $item['quantity'], 0); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
 
                <div class="order-meta">
                    <?php if ($roblox): ?><div><strong>Roblox Account:</strong> <?php echo htmlspecialchars($roblox); ?></div><?php endif; ?>
                    <?php if ($order['phone']): ?><div><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></div><?php endif; ?>
                    <?php if ($note): ?><div><strong>Note:</strong> <?php echo htmlspecialchars($note); ?></div><?php endif; ?>
                    <?php if ($invoice): ?><div><strong><?php echo htmlspecialchars($invoice); ?></strong></div><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
 
<script>
function toggleOrder(id) {
    var el = document.getElementById('order-' + id);
    el.classList.toggle('open');
}
</script>
</body>
</html>