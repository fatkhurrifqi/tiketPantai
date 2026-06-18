<?php
// API: Admin Users - GET /api/admin/users.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$pdo = require __DIR__ . '/../../db.php';

$stmt = $pdo->query('SELECT id, email, name, role, phone, created_at, updated_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

echo json_encode(['users' => $users]);
