<?php
// API: Admin Stats - GET /api/admin/stats.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$pdo = require __DIR__ . '/../../db.php';

// Total users
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

// Total orders
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

// Total revenue (paid + completed)
$revenueStmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('paid', 'completed')");
$totalRevenue = (int)$revenueStmt->fetchColumn();

// Orders by status
$statusStmt = $pdo->query('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
$ordersByStatus = [];
while ($row = $statusStmt->fetch()) {
    $ordersByStatus[$row['status']] = (int)$row['count'];
}

// Recent orders
$recentStmt = $pdo->query('
    SELECT o.*, d.name as dest_name, d.location as dest_location,
           u.name as user_name, u.email as user_email
    FROM orders o
    JOIN destinations d ON o.destination_id = d.id
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
');
$recentOrders = $recentStmt->fetchAll();

foreach ($recentOrders as &$order) {
    $order['destination'] = [
        'id' => $order['destination_id'],
        'name' => $order['dest_name'],
        'location' => $order['dest_location'],
    ];
    $order['user'] = [
        'id' => $order['user_id'],
        'name' => $order['user_name'],
        'email' => $order['user_email'],
    ];
    $order['totalAmount'] = $order['total_amount'];
    unset($order['dest_name'], $order['dest_location'], $order['user_name'], $order['user_email'],
          $order['total_amount'], $order['order_number'], $order['payment_method'],
          $order['visit_date'], $order['created_at'], $order['updated_at'], $order['destination_id'], $order['user_id']);
}

echo json_encode([
    'totalUsers' => (int)$totalUsers,
    'totalOrders' => (int)$totalOrders,
    'totalRevenue' => $totalRevenue,
    'ordersByStatus' => $ordersByStatus,
    'recentOrders' => $recentOrders,
]);
