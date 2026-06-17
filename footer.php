<?php if (!isset($base)) $base = ''; ?>
<footer>
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="footer-logo">Ro-Fruit</div>
            <p class="footer-tagline">The #1 Blox Fruits item shop.<br>Rare fruits, instant delivery to your account.</p>
        </div>
        <div class="footer-col">
            <div class="footer-col-title">Shop</div>
            <a href="<?php echo $base; ?>shop.php">All Fruits</a>
            <a href="<?php echo $base; ?>shop.php?cat=1">🌟 Legendary</a>
            <a href="<?php echo $base; ?>shop.php?cat=2">🔮 Mythic</a>
            <a href="<?php echo $base; ?>shop.php?cat=3">👑 Divine</a>
        </div>
        <div class="footer-col">
            <div class="footer-col-title">Account</div>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="<?php echo $base; ?>pages/orders.php">My Orders</a>
                <a href="<?php echo $base; ?>pages/cart.php">My Cart</a>
                <a href="<?php echo $base; ?>pages/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base; ?>pages/login.php">Login</a>
                <a href="<?php echo $base; ?>pages/register.php">Register</a>
            <?php endif; ?>
        </div>
        <div class="footer-col">
            <div class="footer-col-title">Follow Us</div>
            <a href="https://discord.gg/YHVsF38J" target="_blank">Discord</a>
            <a href="https://www.instagram.com/tipen_as/" target="_blank">Instagram</a>
            <a href="https://youtu.be/dQw4w9WgXcQ?si=Ouawnnle0TSpNBYN" target="_blank">YouTube</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 Ro-Fruit. All rights reserved. Not affiliated with Roblox Corporation or Blox Fruits. &nbsp;|&nbsp; <a href="<?php echo $base; ?>pages/terms.php" style="color:rgba(255,255,255,0.3);text-decoration:none;">Terms & Conditions</a></p>
    </div>
</footer>
<style>
footer { background:#070B14; border-top:1px solid rgba(255,184,0,0.12); margin-top:auto; }
.footer-inner { max-width:1200px; margin:0 auto; padding:48px 30px 32px; display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:40px; }
.footer-logo { font-family:'Cinzel Decorative',serif; color:#FFB800; font-size:22px; margin-bottom:12px; text-shadow:0 0 15px rgba(255,184,0,0.3); }
.footer-tagline { font-size:13px; color:rgba(255,255,255,0.35); line-height:1.7; }
.footer-col-title { font-size:11px; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.3); font-weight:700; margin-bottom:14px; }
.footer-col a { display:block; color:rgba(255,255,255,0.5); text-decoration:none; font-size:14px; margin-bottom:8px; transition:color 0.2s; }
.footer-col a:hover { color:#FFB800; }
.footer-bottom { border-top:1px solid rgba(255,255,255,0.06); text-align:center; padding:16px 30px; font-size:12px; color:rgba(255,255,255,0.2); }
@media(max-width:768px){ .footer-inner{grid-template-columns:1fr 1fr;} }
</style>