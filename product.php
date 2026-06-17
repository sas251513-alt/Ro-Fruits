<?php
include '../includes/db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    die("Invalid product ID.");
}

$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
    
    <!-- Add to Cart Button -->
    <a href="cart.php?add=<?php echo $product['id']; ?>">
        <button>Add to Cart</button>
    </a>
    
    <br><br>
    <a href="cart.php">View Cart</a>
</body>
</html>