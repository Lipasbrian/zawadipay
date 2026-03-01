<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

include_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$data = json_decode(file_get_contents('php://input'));

if(!empty($data->full_name) && !empty($data->phone) && !empty($data->password)) {

    // Generate unique ZWD ID
    $zwd_id = 'ZWD-' . strtoupper(substr($data->country ?? 'KE', 0, 2)) . '-' . rand(10000, 99999);

    // Hash password
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    $query = "INSERT INTO users (full_name, phone, email, password_hash, zwd_id, country, currency)
              VALUES (:full_name, :phone, :email, :password_hash, :zwd_id, :country, :currency)";

    $full_name = $data->full_name;
    $phone = $data->phone;
    $email = $data->email ?? null;
    $country = $data->country ?? 'KE';
    $currency = $data->currency ?? 'KES';

    $stmt = $db->prepare($query);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':zwd_id', $zwd_id);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':currency', $currency);

    if($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully',
            'zwd_id' => $zwd_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
}
?>