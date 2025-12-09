<?php
include 'config/database.php';

header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_GET['username']);
    
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['available' => false]);
    } else {
        echo json_encode(['available' => true]);
    }
} else {
    echo json_encode(['available' => false]);
}
?>