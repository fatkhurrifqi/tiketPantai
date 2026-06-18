<?php
// API: Destinations - GET /api/destinations.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$pdo = require __DIR__ . '/../db.php';

$category = $_GET['category'] ?? null;

if ($category) {
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE is_active = TRUE AND category = ? ORDER BY created_at DESC');
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query('SELECT * FROM destinations WHERE is_active = TRUE ORDER BY created_at DESC');
}

$destinations = $stmt->fetchAll();

// Get ticket types for each destination
foreach ($destinations as &$dest) {
    $ttStmt = $pdo->prepare('SELECT * FROM ticket_types WHERE destination_id = ? ORDER BY price ASC');
    $ttStmt->execute([$dest['id']]);
    $dest['ticketTypes'] = $ttStmt->fetchAll();
    
    // Convert snake_case to camelCase for frontend compatibility
    $dest['isPopular'] = (bool)$dest['is_popular'];
    $dest['isActive'] = (bool)$dest['is_active'];
    $dest['openHours'] = $dest['open_hours'];
    unset($dest['is_popular'], $dest['is_active'], $dest['open_hours']);
}

echo json_encode(['destinations' => $destinations]);
