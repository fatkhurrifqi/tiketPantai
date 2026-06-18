<?php
// =============================================
// Handler CRUD Destinasi (admin only)
// Aksi via field 'action': create | update | delete | toggle_active
// Upload gambar ke uploads/destinations/
// =============================================
session_start();
$pdo = require __DIR__ . '/../db.php';
$user = $_SESSION['user'] ?? null;

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$baseUrl = 'index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $baseUrl);
    exit;
}

/** Bikin slug dari nama */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'destinasi-' . substr(uniqid('', true), -5);
}

/** Pastikan slug unik (tambah suffix -2, -3, dst bila perlu) */
function unique_slug(PDO $pdo, string $slug, ?int $excludeId = null): string
{
    $base = $slug;
    $i = 1;
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM destinations WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM destinations WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . (++$i);
    }
}

/** Proses upload gambar. Return path relatif atau null bila gagal/tidak ada upload */
function handle_upload(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return null;
    }
    // Validasi tipe mime sebenarnya
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMime, true)) {
        return null;
    }
    $dir = __DIR__ . '/../uploads/destinations';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $filename = uniqid('dest_', true) . '.' . $ext;
    $target = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    return 'uploads/destinations/' . $filename;
}

$action = $_POST['action'] ?? '';

try {
    // ---- Hapus ----
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM destinations WHERE id = ?');
        $stmt->execute([$id]);
        $msg = $stmt->rowCount() ? 'dest_deleted' : 'dest_notfound';
        header('Location: ' . $baseUrl . '?msg=' . $msg);
        exit;
    }

    // ---- Toggle aktif/nonaktif ----
    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE destinations SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: ' . $baseUrl . '?msg=dest_toggled');
        exit;
    }

    // ---- Create / Update ----
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    if ($name === '') {
        header('Location: ' . $baseUrl . '?msg=dest_invalid');
        exit;
    }
    $location    = trim($_POST['location'] ?? '');
    $rating      = min(5, max(0, (float)($_POST['rating'] ?? 0)));
    $openHours   = trim($_POST['open_hours'] ?? '');
    $price       = max(0, (int)($_POST['price'] ?? 0));
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '') ?: 'Obyek Wisata';
    $isPopular   = isset($_POST['is_popular']) ? 1 : 0;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    // Gambar (opsional)
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = handle_upload($_FILES['image']);
        if ($imagePath === null) {
            header('Location: ' . $baseUrl . '?msg=dest_img_invalid');
            exit;
        }
    }

    if ($action === 'create') {
        $slug = unique_slug($pdo, slugify($name));
        // Default gambar placeholder bila tidak diupload
        if ($imagePath === null) {
            $imagePath = 'assets/no-image.svg';
        }
        $stmt = $pdo->prepare('INSERT INTO destinations (name, slug, image, location, rating, open_hours, price, description, category, is_popular, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $imagePath, $location, $rating, $openHours, $price, $description, $category, $isPopular, $isActive]);
        header('Location: ' . $baseUrl . '?msg=dest_created');
        exit;
    }

    if ($action === 'update') {
        if (!$id) {
            header('Location: ' . $baseUrl . '?msg=dest_invalid');
            exit;
        }
        if ($imagePath !== null) {
            $stmt = $pdo->prepare('UPDATE destinations SET name=?, image=?, location=?, rating=?, open_hours=?, price=?, description=?, category=?, is_popular=?, is_active=? WHERE id=?');
            $stmt->execute([$name, $imagePath, $location, $rating, $openHours, $price, $description, $category, $isPopular, $isActive, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE destinations SET name=?, location=?, rating=?, open_hours=?, price=?, description=?, category=?, is_popular=?, is_active=? WHERE id=?');
            $stmt->execute([$name, $location, $rating, $openHours, $price, $description, $category, $isPopular, $isActive, $id]);
        }
        header('Location: ' . $baseUrl . '?msg=dest_updated');
        exit;
    }

    // Aksi tidak dikenal
    header('Location: ' . $baseUrl);
    exit;

} catch (PDOException $e) {
    // 1451 = FK constraint (destinasi masih punya pesanan / tiket)
    $message = $e->getMessage();
    $msg = (strpos($message, '1451') !== false || stripos($message, 'foreign key') !== false)
        ? 'dest_in_use'
        : 'dest_error';
    header('Location: ' . $baseUrl . '?msg=' . $msg);
    exit;
}
