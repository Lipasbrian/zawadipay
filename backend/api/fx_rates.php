<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

include_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Seed default rates if table is empty
$check = $db->query("SELECT COUNT(*) FROM fx_rates")->fetchColumn();

if($check == 0) {
    $rates = [
        ['KES', 'NGN', 5.82],
        ['KES', 'GHS', 0.043],
        ['KES', 'UGX', 28.74],
        ['KES', 'TZS', 19.86],
        ['KES', 'XOF', 4.88],
        ['KES', 'ZAR', 0.139],
        ['KES', 'ETB', 1.24],
        ['KES', 'RWF', 10.28],
        ['KES', 'USD', 0.0077],
        ['KES', 'EUR', 0.0071],
    ];

    $stmt = $db->prepare("INSERT INTO fx_rates (from_currency, to_currency, rate) VALUES (?, ?, ?)");
    foreach($rates as $rate) {
        $stmt->execute($rate);
    }
}

$from = $_GET['from'] ?? 'KES';

$query = "SELECT * FROM fx_rates WHERE from_currency = :from ORDER BY to_currency";
$stmt = $db->prepare($query);
$stmt->bindParam(':from', $from);
$stmt->execute();

$rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'base' => $from,
    'rates' => $rates,
    'updated_at' => date('Y-m-d H:i:s')
]);
?>