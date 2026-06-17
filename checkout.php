<?php
session_start();
include '../includes/db_connect.php';
 
// Load bank details
$bank_file = '../includes/bank.json';
// Fetch user's Lucky Box won promo codes
$my_promos = [];
if (isset($_SESSION['user_id'])) {
    $uid2 = $_SESSION['user_id'];
    $pr = $conn->query("SELECT gk.won_code, pc.discount_type, pc.discount_value FROM game_keys gk LEFT JOIN promo_codes pc ON pc.code=gk.won_code WHERE gk.used_by_user=$uid2 AND gk.won_code IS NOT NULL AND pc.is_active=1 ORDER BY gk.used_at DESC");
    if ($pr) while ($row = $pr->fetch_assoc()) $my_promos[] = $row;
}
$bank = file_exists($bank_file)
    ? json_decode(file_get_contents($bank_file), true)
    : ['bank_name'=>'YOUR BANK','account'=>'000000','holder'=>'YOUR NAME','note'=>'Send proof to our socials.'];
$qr_exists = file_exists('../uploads/qr_bank.png');
 
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['cart']))     { header('Location: cart.php');  exit; }
 
$success = ''; $error = ''; $promo_error = '';
 
if (isset($_GET['remove_promo'])) {
    unset($_SESSION['promo']);
    header('Location: checkout.php');
    exit;
}
 
if (isset($_POST['apply_promo'])) {
    $code = strtoupper(trim($_POST['promo_code']));
    if (empty($code)) {
        $promo_error = "Please enter a promo code.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();
        if ($promo) {
            $_SESSION['promo'] = ['code'=>$promo['code'],'type'=>$promo['discount_type'],'value'=>$promo['discount_value']];
            header('Location: checkout.php');
            exit;
        } else {
            $promo_error = "Invalid or expired promo code.";
        }
    }
}
 
if (isset($_POST['place_order'])) {
    $roblox_user  = trim($_POST['roblox_user']);
    $phone        = trim($_POST['phone']);
    $note         = trim($_POST['note']);
    $invoice_type = $_POST['invoice_type'];
    $invoice_val  = trim($_POST['invoice_value'] ?? '');
    $user_id      = $_SESSION['user_id'];
 
    if (empty($roblox_user)) {
        $error = "Please enter your Roblox username.";
    } else {
        $address = "Roblox: " . $roblox_user;
        if (!empty($note)) $address .= " | Note: " . $note;
        if ($invoice_type != 'none' && !empty($invoice_val))
            $address .= " | Invoice (" . $invoice_type . "): " . $invoice_val;
 
        $ids    = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
        $result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
        $products = [];
        while ($p = $result->fetch_assoc()) $products[$p['id']] = $p;
 
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $pid => $qty)
            if (isset($products[$pid])) $subtotal += $products[$pid]['price'] * $qty;
 
        $discount = 0;
        if (isset($_SESSION['promo'])) {
            $pr = $_SESSION['promo'];
            $discount = $pr['type'] == 'percent'
                ? round($subtotal * $pr['value'] / 100)
                : min($pr['value'], $subtotal);
        }
        $total = $subtotal - $discount;
 
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, address, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $user_id, $total, $address, $phone);
 
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
 
            // Insert order items
            $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            // Decrease stock automatically
            $stmt3 = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
 
            foreach ($_SESSION['cart'] as $pid => $qty) {
                if (isset($products[$pid])) {
                    $price = $products[$pid]['price'];
                    $stmt2->bind_param("iiid", $order_id, $pid, $qty, $price);
                    $stmt2->execute();
                    // ← Stock goes down here
                    $stmt3->bind_param("ii", $qty, $pid);
                    $stmt3->execute();
                }
            }
            $_SESSION['cart'] = [];
            unset($_SESSION['promo']);
            $success = "Order #$order_id placed! We will deliver <strong>" . htmlspecialchars($roblox_user) . "</strong>'s fruit in-game. Thank you!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
 
$cart_items = []; $subtotal_display = 0;
if (!empty($_SESSION['cart'])) {
    $ids    = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    $prods  = [];
    while ($p = $result->fetch_assoc()) $prods[$p['id']] = $p;
    foreach ($_SESSION['cart'] as $pid => $qty) {
        if (isset($prods[$pid])) {
            $sub = $prods[$pid]['price'] * $qty;
            $subtotal_display += $sub;
            $cart_items[] = ['name'=>$prods[$pid]['name'],'price'=>$prods[$pid]['price'],'qty'=>$qty,'subtotal'=>$sub];
        }
    }
}
 
$discount_display = 0; $promo_label = '';
if (isset($_SESSION['promo'])) {
    $pr = $_SESSION['promo'];
    if ($pr['type'] == 'percent') {
        $discount_display = round($subtotal_display * $pr['value'] / 100);
        $promo_label = $pr['code'] . ' (-' . $pr['value'] . '%)';
    } else {
        $discount_display = min($pr['value'], $subtotal_display);
        $promo_label = $pr['code'] . ' (-NT$' . number_format($pr['value'], 0) . ')';
    }
}
$total_display = $subtotal_display - $discount_display;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }
        .wrap { max-width:700px; margin:40px auto; padding:0 24px 60px; }
        h1 { font-family:'Cinzel Decorative',serif; font-size:26px; margin-bottom:28px; color:#FFB800; }
        .success-box { background:rgba(40,167,69,0.1); border:1px solid rgba(40,167,69,0.35); color:#4CAF50; padding:24px; border-radius:12px; margin-bottom:24px; line-height:1.7; }
        .success-box h3 { font-size:18px; margin-bottom:8px; }
        .error-box { background:rgba(220,53,69,0.1); border:1px solid rgba(220,53,69,0.35); color:#ff6b6b; padding:14px; border-radius:8px; margin-bottom:20px; }
        .summary-box, .form-box { background:#12182B; border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:22px; margin-bottom:20px; }
        .section-title { font-family:'Cinzel Decorative',serif; font-size:14px; color:#FFB800; margin-bottom:16px; letter-spacing:1px; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:8px; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,0.35); border-bottom:1px solid rgba(255,255,255,0.07); }
        th:not(:first-child) { text-align:center; }
        td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,0.04); font-size:14px; }
        td:not(:first-child) { text-align:center; }
        .subtotal-row td { color:rgba(255,255,255,0.55); font-size:14px; border-bottom:none; }
        .discount-row td { color:#4CAF50; font-size:14px; border-bottom:none; }
        .total-row td { font-size:18px; font-weight:700; border-top:1px solid rgba(255,255,255,0.1); border-bottom:none; padding-top:14px; }
        .total-val { color:#FFB800; }
        label.field-label { font-weight:600; font-size:13px; color:rgba(255,255,255,0.7); display:block; margin-bottom:8px; }
        .optional { color:rgba(255,255,255,0.3); font-weight:400; font-size:12px; margin-left:6px; }
        .field { margin-bottom:20px; } .field:last-child { margin-bottom:0; }
        input[type=text], textarea { width:100%; background:#0B0F1A; border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:#fff; padding:11px 14px; font-family:'Exo 2',sans-serif; font-size:14px; transition:border-color 0.2s; }
        input[type=text]:focus, textarea:focus { outline:none; border-color:#FFB800; }
        textarea { resize:vertical; min-height:80px; }
        .hint { font-size:12px; color:rgba(255,255,255,0.3); margin-top:6px; }
        .promo-social { display:flex; align-items:center; gap:8px; background:rgba(255,184,0,0.07); border:1px dashed rgba(255,184,0,0.35); border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:13px; color:rgba(255,184,0,0.85); font-weight:600; }
        .promo-row { display:flex; gap:0; }
        .promo-row input { flex:1; border-radius:8px 0 0 8px; }
        .btn-apply { background:transparent; border:1px solid #FFB800; border-left:none; color:#FFB800; padding:11px 20px; border-radius:0 8px 8px 0; font-family:'Exo 2',sans-serif; font-size:13px; font-weight:700; cursor:pointer; transition:all 0.2s; }
        .btn-apply:hover { background:#FFB800; color:#0B0F1A; }
        .promo-error { color:#ff6b6b; font-size:13px; margin-top:8px; }
        .promo-applied { display:flex; justify-content:space-between; align-items:center; background:rgba(40,167,69,0.1); border:1px solid rgba(40,167,69,0.3); border-radius:8px; padding:10px 14px; margin-top:10px; color:#4CAF50; font-size:14px; font-weight:600; }
        .remove-promo { color:rgba(255,80,80,0.7); text-decoration:none; font-size:18px; font-weight:700; }
        .remove-promo:hover { color:#ff4444; }
        .invoice-options { display:flex; flex-direction:column; gap:10px; }
        .invoice-opt { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; background:#0B0F1A; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer; transition:border-color 0.2s; }
        .invoice-opt:hover { border-color:rgba(255,184,0,0.4); }
        .invoice-opt input[type=radio] { accent-color:#FFB800; width:16px; height:16px; cursor:pointer; flex-shrink:0; margin-top:2px; }
        .invoice-opt-label { font-size:14px; font-weight:600; }
        .invoice-opt-desc  { font-size:12px; color:rgba(255,255,255,0.35); margin-top:2px; }
        .invoice-input-wrap { margin-top:8px; display:none; }
        .invoice-input-wrap.show { display:block; }
        .btn-place { width:100%; background:#FFB800; color:#0B0F1A; border:none; padding:15px; font-size:16px; font-weight:700; font-family:'Exo 2',sans-serif; border-radius:10px; cursor:pointer; letter-spacing:0.5px; transition:background 0.2s,transform 0.15s; margin-top:8px; }
        .btn-place:hover { background:#FFC933; transform:scale(1.01); }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="cart.php">← Back to Cart</a>
        <a href="orders.php">📋 My Orders</a>
        <span style="color:#FFB800;margin-left:22px;">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
    </div>
</nav>
 
<div class="wrap">
    <h1>Checkout</h1>
    <?php if ($success): ?>
        <div class="success-box">
            <h3>✓ Order Placed!</h3>
            <p><?php echo $success; ?></p>
 
            <!-- Payment instructions -->
            <div style="margin-top:20px;background:#0B0F1A;border:1px solid rgba(255,184,0,0.25);border-radius:10px;padding:20px;">
                <div style="font-family:'Cinzel Decorative',serif;font-size:12px;color:#FFB800;margin-bottom:12px;letter-spacing:1px;">💳 NOW PLEASE PAY</div>
                <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:14px;">Transfer to this account and your order will be confirmed:</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                    <div><div style="font-size:11px;color:rgba(255,255,255,0.3);margin-bottom:3px;">BANK</div><strong><?php echo htmlspecialchars($bank['bank_name']); ?></strong></div>
                    <?php if (!empty($bank['bank_code'])): ?>
                    <div><div style="font-size:11px;color:rgba(255,255,255,0.3);margin-bottom:3px;">BANK CODE / 局號</div><strong style="color:#00BFFF;"><?php echo htmlspecialchars($bank['bank_code']); ?></strong></div>
                    <?php endif; ?>
                    <div><div style="font-size:11px;color:rgba(255,255,255,0.3);margin-bottom:3px;">ACCOUNT</div><strong style="color:#FFB800;"><?php echo htmlspecialchars($bank['account']); ?></strong></div>
                    <div><div style="font-size:11px;color:rgba(255,255,255,0.3);margin-bottom:3px;">HOLDER</div><strong><?php echo htmlspecialchars($bank['holder']); ?></strong></div>
                </div>
                <?php if ($qr_exists): ?>
                <div style="text-align:center;margin-bottom:12px;">
                    <img src="../uploads/qr_bank.png" alt="Bank QR Code" style="max-width:170px;border-radius:8px;border:2px solid rgba(255,184,0,0.3);">
                    <div style="font-size:12px;color:rgba(255,255,255,0.35);margin-top:6px;">Scan to pay</div>
                </div>
                <?php endif; ?>
                <p style="font-size:13px;color:rgba(255,255,255,0.4);line-height:1.6;"><?php echo htmlspecialchars($bank['note']); ?></p>
            </div>
 
            <p style="margin-top:16px;">
                <a href="orders.php" style="color:#FFB800;font-weight:700;">📋 View My Orders</a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="../index.php" style="color:rgba(255,255,255,0.4);">← Home</a>
            </p>
        </div>
    <?php else: ?>
        <?php if ($error): ?><div class="error-box">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
 
        <div class="summary-box">
            <div class="section-title">Order Summary</div>
            <table>
                <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr><td><?php echo htmlspecialchars($item['name']); ?></td><td><?php echo $item['qty']; ?></td><td>NT$<?php echo number_format($item['price'],0); ?></td><td>NT$<?php echo number_format($item['subtotal'],0); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="subtotal-row"><td colspan="3">Subtotal</td><td>NT$<?php echo number_format($subtotal_display,0); ?></td></tr>
                    <?php if ($discount_display > 0): ?>
                    <tr class="discount-row"><td colspan="3">🎟️ Discount (<?php echo htmlspecialchars($promo_label); ?>)</td><td>- NT$<?php echo number_format($discount_display,0); ?></td></tr>
                    <?php endif; ?>
                    <tr class="total-row"><td colspan="3">Total</td><td class="total-val">NT$<?php echo number_format($total_display,0); ?></td></tr>
                </tfoot>
            </table>
        </div>
 
        <div class="form-box">
            <div class="section-title">🎟️ Promo Code</div>
            <?php if (!empty($my_promos)): ?>
            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:rgba(255,255,255,0.35);margin-bottom:8px;letter-spacing:0.5px;">🎲 YOUR LUCKY BOX CODES — click to apply:</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($my_promos as $mp):
                    $disc = $mp['discount_type']==="percent" ? $mp['discount_value'].'% Off' : 'NT$'.$mp['discount_value'].' Off';
                ?>
                    <button type="button"
                        onclick="applyCode('<?php echo htmlspecialchars($mp['won_code']); ?>')"
                        style="background:rgba(255,184,0,0.1);border:1px solid rgba(255,184,0,0.35);color:#FFB800;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Exo 2',sans-serif;transition:background 0.2s;">
                        <?php echo htmlspecialchars($mp['won_code']); ?> · <?php echo $disc; ?>
                    </button>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="promo-social">📱 Follow Our Socials to get the Codes!</div>
            <?php if (isset($_SESSION['promo'])): ?>
                <div class="promo-applied">
                    <span>✓ Code <strong><?php echo htmlspecialchars($_SESSION['promo']['code']); ?></strong> applied!</span>
                    <a href="checkout.php?remove_promo=1" class="remove-promo">✕</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="promo-row">
                        <input type="text" name="promo_code" placeholder="Enter promo code" value="<?php echo isset($_POST['promo_code'])?htmlspecialchars($_POST['promo_code']):''; ?>">
                        <button type="submit" name="apply_promo" class="btn-apply">Apply</button>
                    </div>
                    <?php if ($promo_error): ?><p class="promo-error">⚠ <?php echo htmlspecialchars($promo_error); ?></p><?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
 
        <form method="POST">
            <div class="form-box">
                <div class="section-title">Account Details</div>
                <div class="field">
                    <label class="field-label">Roblox Username *</label>
                    <input type="text" name="roblox_user" placeholder="Enter your Roblox username" value="<?php echo isset($_POST['roblox_user'])?htmlspecialchars($_POST['roblox_user']):''; ?>" required>
                    <p class="hint">We will send the fruit to this account in-game.</p>
                </div>
                <div class="field">
                    <label class="field-label">Phone Number <span class="optional">(optional)</span></label>
                    <input type="text" name="phone" placeholder="e.g. 0912 345 678" value="<?php echo isset($_POST['phone'])?htmlspecialchars($_POST['phone']):''; ?>">
                </div>
                <div class="field">
                    <label class="field-label">Note <span class="optional">(optional)</span></label>
                    <textarea name="note" placeholder="Any special instructions?"><?php echo isset($_POST['note'])?htmlspecialchars($_POST['note']):''; ?></textarea>
                </div>
            </div>
            <div class="form-box">
                <div class="section-title">電子發票 Electronic Invoice</div>
                <div class="invoice-options">
                    <label class="invoice-opt"><input type="radio" name="invoice_type" value="none" checked onchange="toggleInvoice(this)"><div><div class="invoice-opt-label">不需要發票 No invoice needed</div></div></label>
                    <label class="invoice-opt"><input type="radio" name="invoice_type" value="mobile" onchange="toggleInvoice(this)"><div style="flex:1;"><div class="invoice-opt-label">手機條碼 Mobile Barcode</div><div class="invoice-opt-desc">Enter your carrier barcode (e.g. /ABC1234)</div><div class="invoice-input-wrap" id="wrap-mobile"><input type="text" name="invoice_value" placeholder="/XXXXXXX" maxlength="8" style="margin-top:8px;"></div></div></label>
                    <label class="invoice-opt"><input type="radio" name="invoice_type" value="donate" onchange="toggleInvoice(this)"><div style="flex:1;"><div class="invoice-opt-label">捐贈發票 Donate Invoice</div><div class="invoice-opt-desc">Donate your invoice to a charity</div><div class="invoice-input-wrap" id="wrap-donate"><input type="text" name="invoice_value" placeholder="Donation code (e.g. 8585)" maxlength="7" style="margin-top:8px;"></div></div></label>
                </div>
            </div>
                        <!-- Payment Method -->
            <div class="form-box">
                <div class="section-title">💳 Payment Method</div>
                <div style="background:#0B0F1A;border:1px solid rgba(255,184,0,0.25);border-radius:10px;padding:18px;">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                        <div style="width:20px;height:20px;border-radius:50%;border:2px solid #FFB800;background:#FFB800;flex-shrink:0;"></div>
                        <div style="font-weight:700;font-size:15px;">🏦 Bank Transfer</div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:<?php echo $qr_exists?'0':'0'; ?>;">
                        <div><div style="font-size:11px;color:rgba(255,255,255,0.35);margin-bottom:3px;">BANK NAME</div><div style="font-weight:600;"><?php echo htmlspecialchars($bank['bank_name']); ?></div></div>
                        <?php if (!empty($bank['bank_code'])): ?>
                        <div><div style="font-size:11px;color:rgba(255,255,255,0.35);margin-bottom:3px;">BANK CODE / 局號</div><div style="font-weight:700;color:#00BFFF;"><?php echo htmlspecialchars($bank['bank_code']); ?></div></div>
                        <?php endif; ?>
                        <div><div style="font-size:11px;color:rgba(255,255,255,0.35);margin-bottom:3px;">ACCOUNT NUMBER</div><div style="font-weight:700;color:#FFB800;font-size:16px;"><?php echo htmlspecialchars($bank['account']); ?></div></div>
                        <div><div style="font-size:11px;color:rgba(255,255,255,0.35);margin-bottom:3px;">ACCOUNT HOLDER</div><div style="font-weight:600;"><?php echo htmlspecialchars($bank['holder']); ?></div></div>
                    </div>
                    <?php if ($qr_exists): ?>
                    <div style="text-align:center;margin-top:16px;">
                        <img src="../uploads/qr_bank.png" alt="Bank QR" style="max-width:160px;border-radius:8px;border:2px solid rgba(255,184,0,0.3);">
                        <div style="font-size:12px;color:rgba(255,255,255,0.35);margin-top:6px;">📷 Scan QR code to pay</div>
                    </div>
                    <?php endif; ?>
                    <div style="margin-top:14px;font-size:13px;color:rgba(255,255,255,0.4);line-height:1.6;">
                        ℹ️ <?php echo htmlspecialchars($bank['note']); ?>
                    </div>
                </div>
            </div>
 
            <button type="submit" name="place_order" class="btn-place">✓ Place Order</button>
        </form>
    <?php endif; ?>
</div>
<script>
function toggleInvoice(radio) {
    document.querySelectorAll('.invoice-input-wrap').forEach(function(el){el.classList.remove('show');});
    var wrap=document.getElementById('wrap-'+radio.value);
    if(wrap) wrap.classList.add('show');
}
document.addEventListener('DOMContentLoaded',function(){
    var checked=document.querySelector('input[name="invoice_type"]:checked');
    if(checked) toggleInvoice(checked);
});
function applyCode(code) {
    var input = document.querySelector('input[name="promo_code"]');
    if (input) {
        input.value = code;
        var btn = document.querySelector('button[name="apply_promo"]');
        if (btn) btn.click();
    }
}
</script>
</body>
</html>