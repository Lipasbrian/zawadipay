<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

include_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$user_id = $_GET['user_id'] ?? null;
$limit = $_GET['limit'] ?? 20;
$offset = $_GET['offset'] ?? 0;

if(!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$query = "SELECT 
            t.*,
            s.full_name as sender_name,
            s.phone as sender_phone,
            s.zwd_id as sender_zwd,
            r.full_name as receiver_name,
            r.phone as receiver_phone,
            r.zwd_id as receiver_zwd
          FROM transactions t
          LEFT JOIN users s ON t.sender_id = s.id
          LEFT JOIN users r ON t.receiver_id = r.id
          WHERE t.sender_id = :user_id OR t.receiver_id = :user_id2
          ORDER BY t.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':user_id2', $user_id);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'count' => count($transactions),
    'transactions' => $transactions
]);
?>