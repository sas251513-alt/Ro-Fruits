<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms & Conditions - Ro-Fruit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0B0F1A; color:#fff; font-family:'Exo 2',sans-serif; min-height:100vh; display:flex; flex-direction:column; }
        nav { background:rgba(11,15,26,0.98); border-bottom:1px solid rgba(255,184,0,0.15); padding:16px 40px; display:flex; justify-content:space-between; align-items:center; }
        .nav-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:20px; text-decoration:none; }
        .nav-links a { color:#aaa; text-decoration:none; margin-left:22px; font-size:14px; font-weight:600; transition:color 0.2s; }
        .nav-links a:hover { color:#FFB800; }

        .wrap { max-width:760px; margin:0 auto; padding:50px 24px 80px; flex:1; }

        .page-title { font-family:'Cinzel Decorative',serif; font-size:28px; color:#FFB800; margin-bottom:6px; }
        .page-date   { font-size:13px; color:rgba(255,255,255,0.3); margin-bottom:40px; }

        .section { margin-bottom:36px; }
        .section h2 { font-size:16px; font-weight:700; color:#FFB800; margin-bottom:12px; letter-spacing:0.5px; padding-bottom:8px; border-bottom:1px solid rgba(255,184,0,0.15); }
        .section p  { font-size:14px; color:rgba(255,255,255,0.55); line-height:1.85; margin-bottom:10px; }
        .section ul { padding-left:20px; }
        .section ul li { font-size:14px; color:rgba(255,255,255,0.55); line-height:1.85; margin-bottom:6px; }
        .section ul li::marker { color:#FFB800; }

        .highlight-box { background:#12182B; border:1px solid rgba(255,184,0,0.2); border-radius:10px; padding:18px 20px; margin-bottom:10px; font-size:14px; color:rgba(255,255,255,0.6); line-height:1.7; }
        .highlight-box strong { color:#FFB800; }

        .disclaimer { background:rgba(220,53,69,0.07); border:1px solid rgba(220,53,69,0.2); border-radius:10px; padding:16px 20px; font-size:13px; color:rgba(255,150,150,0.8); line-height:1.7; margin-top:36px; }
    </style>
</head>
<body>
<nav>
    <a href="../index.php" class="nav-logo">Ro-Fruit</a>
    <div class="nav-links">
        <a href="../shop.php">Shop</a>
        <?php if (isset($_SESSION['username'])): ?>
            <a href="orders.php">My Orders</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="wrap">
    <h1 class="page-title">Terms & Conditions</h1>
    <p class="page-date">Last updated: June 2026</p>

    <div class="section">
        <h2>1. About Ro-Fruit</h2>
        <p>Ro-Fruit is an online store that sells in-game items for the Roblox game Blox Fruits. By using this website and placing an order, you agree to these terms and conditions in full.</p>
        <div class="highlight-box">
            <strong>Important:</strong> Ro-Fruit is an independent service and is <strong>not affiliated with, endorsed by, or connected to Roblox Corporation or the developers of Blox Fruits</strong> in any way.
        </div>
    </div>

    <div class="section">
        <h2>2. Digital Goods</h2>
        <p>All products sold on Ro-Fruit are in-game digital items delivered within the Roblox game Blox Fruits. By purchasing, you understand:</p>
        <ul>
            <li>Items are virtual and have no real-world monetary value outside the game.</li>
            <li>Delivery is done manually by our team in-game to your Roblox account.</li>
            <li>You must provide your correct Roblox username at checkout for delivery.</li>
            <li>Delivery times may vary depending on server availability and our team's schedule.</li>
        </ul>
    </div>

    <div class="section">
        <h2>3. Payment</h2>
        <p>All payments are processed via bank transfer. Orders are placed first, then payment is expected within 24 hours.</p>
        <ul>
            <li>Bank transfer details are provided on the checkout page and in your order confirmation.</li>
            <li>After transferring, you must send payment proof to our Discord or Instagram.</li>
            <li>Orders that remain unpaid after 24 hours may be cancelled.</li>
            <li>All prices are listed in New Taiwan Dollars (NT$).</li>
        </ul>
    </div>

    <div class="section">
        <h2>4. Refund Policy</h2>
        <p>Due to the nature of digital goods, <strong>all sales are final</strong>. We do not offer refunds once an item has been delivered to your Roblox account.</p>
        <p>Exceptions may be considered in the following cases:</p>
        <ul>
            <li>Item was not delivered within a reasonable time after confirmed payment.</li>
            <li>The wrong item was delivered.</li>
            <li>The item becomes unavailable due to a game update (in this case a replacement or store credit will be offered).</li>
        </ul>
        <p>To request an exception, contact us via Discord or Instagram with your order number and proof of payment.</p>
    </div>

    <div class="section">
        <h2>5. Your Account</h2>
        <ul>
            <li>You are responsible for keeping your login credentials secure.</li>
            <li>You must provide accurate information including your Roblox username.</li>
            <li>We reserve the right to suspend accounts found to be abusing the platform.</li>
            <li>You must be of appropriate age as required by Roblox's own terms of service.</li>
        </ul>
    </div>

    <div class="section">
        <h2>6. Promo Codes</h2>
        <ul>
            <li>Promo codes are one-time use unless otherwise stated.</li>
            <li>Codes cannot be exchanged for cash or combined with other offers.</li>
            <li>Ro-Fruit reserves the right to deactivate promo codes at any time.</li>
        </ul>
    </div>

    <div class="section">
        <h2>7. Changes to These Terms</h2>
        <p>Ro-Fruit reserves the right to update these terms at any time. Continued use of the website after changes are posted constitutes your acceptance of the new terms.</p>
    </div>

    <div class="section">
        <h2>8. Contact Us</h2>
        <p>If you have any questions about these terms, please reach out to us through our social channels:</p>
        <ul>
            <li>Discord: <a href="https://discord.gg/YHVsF38J" target="_blank" style="color:#5865F2;">discord.gg/YHVsF38J</a></li>
            <li>Instagram: <a href="https://www.instagram.com/tipen_as/" target="_blank" style="color:#E1306C;">@tipen_as</a></li>
        </ul>
    </div>

    <div class="disclaimer">
        ⚠ <strong>Disclaimer:</strong> Ro-Fruit is not affiliated with Roblox Corporation or the Blox Fruits development team. All game-related trademarks and content belong to their respective owners. Use of in-game items is subject to Roblox's own terms of service.
    </div>
</div>

<?php $base = '../'; include '../includes/footer.php'; ?>
</body>
</html>
