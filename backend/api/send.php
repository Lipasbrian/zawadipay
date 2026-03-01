<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

include_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$data = json_decode(file_get_contents('php://input'));

if(!empty($data->sender_id) && !empty($data->receiver_phone) && !empty($data->amount) && !empty($data->method)) {

    // Check sender exists and has enough balance
    $query = "SELECT * FROM users WHERE id = :sender_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sender_id', $data->sender_id);
    $stmt->execute();
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$sender) {
        echo json_encode(['success' => false, 'message' => 'Sender not found']);
        exit;
    }

    if($sender['balance'] < $data->amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }

    // Find receiver
    $query = "SELECT * FROM users WHERE phone = :phone OR zwd_id = :zwd_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':phone', $data->receiver_phone);
    $stmt->bindParam(':zwd_id', $data->receiver_phone);
    $stmt->execute();
    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$receiver) {
        echo json_encode(['success' => false, 'message' => 'Recipient not found']);
        exit;
    }

    // Generate reference
    $reference = 'ZWD' . strtoupper(uniqid());

    // Begin transaction
    $db->beginTransaction();

    try {
        // Deduct from sender
        $query = "UPDATE users SET balance = balance - :amount WHERE id = :sender_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':amount', $data->amount);
        $stmt->bindParam(':sender_id', $data->sender_id);
        $stmt->execute();

        // Add to receiver
        $query = "UPDATE users SET balance = balance + :amount WHERE id = :receiver_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':amount', $data->amount);
        $stmt->bindParam(':receiver_id', $receiver['id']);
        $stmt->execute();

        // Record transaction
        $query = "INSERT INTO transactions 
                  (sender_id, receiver_id, amount, currency, method, status, reference, note)
                  VALUES (:sender_id, :receiver_id, :amount, :currency, :method, 'completed', :reference, :note)";
        $stmt = $db->prepare($query);
        $sender_id = $data->sender_id;
        $receiver_id = $receiver['id'];
        $amount = $data->amount;
        $currency = $sender['currency'];
        $method = $data->method;
        $note = $data->note ?? '';
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':currency', $currency);
        $stmt->bindParam(':method', $method);
        $stmt->bindParam(':reference', $reference);
        $stmt->bindParam(':note', $note);
        $stmt->execute();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Transfer successful',
            'reference' => $reference,
            'amount' => $data->amount,
            'recipient' => $receiver['full_name']
        ]);

    } catch(Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
}
?>