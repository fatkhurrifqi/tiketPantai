<?php
// =============================================
// Handler CRUD Ticket Types / Fasilitas (admin only)
// Aksi via field 'action': ticket_create | ticket_update | ticket_delete
// =============================================
session_start();
$pdo = require __DIR__ . '/../db.php';
$user = $_SESSION['user'] ?? null;

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action        = $_POST['action'] ?? '';
$destinationId = (int)($_POST['destination_id'] ?? 0);

function redirect_msg(int $destinationId, string $code): void
{
    header('Location: tickets.php?destination_id=' . $destinationId . '&msg=' . $code);
    exit;
}

try {
    // ---- Hapus ----
    if ($action === 'ticket_delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM ticket_types WHERE id = ?');
        $stmt->execute([$id]);
        redirect_msg($destinationId, $stmt->rowCount() ? 'ticket_deleted' : 'ticket_invalid');
    }

    // ---- Create / Update ----
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $price = max(0, (int)($_POST['price'] ?? 0));
    $unit  = trim($_POST['unit'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    if ($name === '' || !$destinationId) {
        redirect_msg($destinationId, 'ticket_invalid');
    }

    if ($action === 'ticket_create') {
        $stmt = $pdo->prepare('INSERT INTO ticket_types (name, price, unit, description, destination_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $price, $unit, $desc, $destinationId]);
        redirect_msg($destinationId, 'ticket_created');
    }

    if ($action === 'ticket_update') {
        if (!$id) {
            redirect_msg($destinationId, 'ticket_invalid');
        }
        $stmt = $pdo->prepare('UPDATE ticket_types SET name = ?, price = ?, unit = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $price, $unit, $desc, $id]);
        redirect_msg($destinationId, 'ticket_updated');
    }

    // Aksi tidak dikenal
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    // 1451 = FK constraint (tiket sudah dipakai di order_items)
    $message = $e->getMessage();
    $code = (strpos($message, '1451') !== false || stripos($message, 'foreign key') !== false)
        ? 'ticket_in_use'
        : 'ticket_error';
    redirect_msg($destinationId, $code);
}
