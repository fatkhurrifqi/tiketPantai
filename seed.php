<?php
// Seed script - Run once: php seed.php
// Creates admin and demo users with hashed passwords

$pdo = require __DIR__ . '/db.php';

$users = [
    ['email' => 'admin@tiketpantai.com', 'password' => 'admin123', 'name' => 'Admin TiketPantai', 'role' => 'admin', 'phone' => '0857-2826-9876'],
    ['email' => 'user@example.com', 'password' => 'user123', 'name' => 'Budi Santoso', 'role' => 'user', 'phone' => '0812-9476-1810'],
];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT IGNORE INTO users (email, password_hash, name, role, phone) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$u['email'], $hash, $u['name'], $u['role'], $u['phone']]);
    echo "✅ User: {$u['email']} (password: {$u['password']})\n";
}

echo "\n🎉 Seed selesai!\n";
