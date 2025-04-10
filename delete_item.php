<?php
require_once 'config.php';
checkAuth();

if (!isAdmin()) {
    header("Location: index.php?error=Only admin can delete items");
    exit();
}

if (isset($_GET['item_code'])) {
    $itemCode = $_GET['item_code'];
    
    // First delete related transactions
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE item_code = ?");
    $stmt->execute([$itemCode]);
    
    // Then delete the item
    $stmt = $pdo->prepare("DELETE FROM items WHERE item_code = ?");
    $stmt->execute([$itemCode]);
    
    header("Location: index.php?tab=items&success=Item deleted successfully");
    exit();
} else {
    header("Location: index.php?tab=items");
    exit();
}
?>