<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $reel_number = trim($_POST['reel_number']);

    $stmt = $conn->prepare("UPDATE drop_wire_consumption SET reel_number = ? WHERE id = ?");
    $stmt->bind_param("si", $reel_number, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>
