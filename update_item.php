<?php
require_once 'config.php';
checkAuth();

if (!isAdmin()) {
    header("Location: index.php?error=Only admin can update items");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemCode = $_POST['item_code'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $currentStock = $_POST['current_stock'];
    $minStock = $_POST['min_stock'];
    
    $stmt = $pdo->prepare("UPDATE items SET description = ?, category = ?, current_stock = ?, min_stock = ? WHERE item_code = ?");
    $stmt->execute([$description, $category, $currentStock, $minStock, $itemCode]);
    
    header("Location: index.php?tab=items&success=Item updated successfully");
    exit();
} else {
    header("Location: index.php?tab=items");
    exit();
}
?>