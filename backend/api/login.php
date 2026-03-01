<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

include_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$data = json_decode(file_get_contents('php://input'));

if(!empty($data->phone) && !empty($data->password)) {

    $query = "SELECT * FROM users WHERE phone = :phone LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($data->password, $user['password_hash'])) {
        // Generate simple session token
        $token = bin2hex(random_bytes(32));

        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'],
                'zwd_id' => $user['zwd_id'],
                'balance' => $user['balance'],
                'country' => $user['country'],
                'currency' => $user['currency']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid phone or password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phone and password required']);
}
?>