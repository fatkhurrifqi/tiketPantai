<?php
// API: Update Order Status - PATCH /api/orders/update.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

$pdo = require __DIR__ . '/../../db.php';

// Get order ID from query param
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$status = $data['status'] ?? null;

$validStatuses = ['pending', 'paid', 'cancelled', 'completed'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Status tidak valid']);
    exit;
}

$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$status, $orderId]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Pesanan tidak ditemukan']);
    exit;
}

echo json_encode(['success' => true, 'status' => $status]);
