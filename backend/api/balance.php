<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

include_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$user_id = $_GET['user_id'] ?? null;

if(!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$query = "SELECT id, full_name, phone, zwd_id, balance, currency, country, kyc_verified 
          FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user) {
    echo json_encode([
        'success' => true,
        'balance' => $user['balance'],
        'currency' => $user['currency'],
        'user' => $user
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>