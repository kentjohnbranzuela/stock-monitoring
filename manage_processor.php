<?php
require_once 'config.php';
checkAuth();
if (!isAdmin()) die("Admin access only");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO processors (name, is_active) VALUES (?, ?)");
            $stmt->execute([$_POST['name'], $_POST['is_active']]);
            $message = "Processor added successfully";
        } 
        elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE processors SET name = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['is_active'], $_POST['id']]);
            $message = "Processor updated successfully";
        }
        elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM processors WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Processor deleted successfully";
        }
        
        header("Location: index.php?tab=processors&success=" . urlencode($message));
        exit();
    } catch (PDOException $e) {
        header("Location: index.php?tab=processors&error=" . urlencode("Database error: " . $e->getMessage()));
        exit();
    }
}