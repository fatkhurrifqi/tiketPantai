<?php
// =============================================
// Handler Ulasan (review) - upsert (1 ulasan/user/destinasi)
// Setiap ulasan baru akan menambah jumlah ulasan (destinations.reviews)
// dan memperbarui rating rata-rata (destinations.rating).
// =============================================
session_start();
$pdo = require __DIR__ . '/db.php';
$user = $_SESSION['user'] ?? null;

if (!$user) {
    header('Location: auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$destinationId = (int)($_POST['destination_id'] ?? 0);
$rating        = (int)($_POST['rating'] ?? 0);
$comment       = trim($_POST['comment'] ?? '');

// Ambil slug untuk redirect kembali
$destStmt = $pdo->prepare('SELECT slug FROM destinations WHERE id = ?');
$destStmt->execute([$destinationId]);
$slug = $destStmt->fetchColumn();

if (!$slug) {
    header('Location: index.php');
    exit;
}

$back = 'destination.php?wisata=' . urlencode($slug);

// Validasi rating
if ($rating < 1 || $rating > 5) {
    header('Location: ' . $back . '&review_error=' . urlencode('Rating harus antara 1 sampai 5 bintang.'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Sudah pernah ulas destinasi ini?
    $existStmt = $pdo->prepare('SELECT rating FROM reviews WHERE destination_id = ? AND user_id = ?');
    $existStmt->execute([$destinationId, $user['id']]);
    $oldRating = $existStmt->fetchColumn(); // false bila belum ada

    // Aggregate destinasi saat ini
    $aggStmt = $pdo->prepare('SELECT rating, reviews FROM destinations WHERE id = ?');
    $aggStmt->execute([$destinationId]);
    $agg       = $aggStmt->fetch();
    $curRating = (float)$agg['rating'];
    $curCount  = (int)$agg['reviews'];

    if ($oldRating === false) {
        // ---- Ulasan BARU: insert + tambah jumlah + hitung ulang rata-rata ----
        $ins = $pdo->prepare('INSERT INTO reviews (destination_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');
        $ins->execute([$destinationId, $user['id'], $rating, $comment]);

        $newCount  = $curCount + 1;
        $newRating = $newCount > 0 ? (($curRating * $curCount) + $rating) / $newCount : $rating;
    } else {
        // ---- Ulasan di-EDIT: update + sesuaikan rata-rata sesuai perubahan rating ----
        $upd = $pdo->prepare('UPDATE reviews SET rating = ?, comment = ? WHERE destination_id = ? AND user_id = ?');
        $upd->execute([$rating, $comment, $destinationId, $user['id']]);

        $oldRating = (float)$oldRating;
        $newCount  = $curCount;
        $newRating = $curCount > 0 ? $curRating + (($rating - $oldRating) / $curCount) : $rating;
    }

    // Simpan aggregate yang sudah diperbarui
    $updAgg = $pdo->prepare('UPDATE destinations SET rating = ?, reviews = ? WHERE id = ?');
    $updAgg->execute([round($newRating, 1), $newCount, $destinationId]);

    $pdo->commit();
    header('Location: ' . $back . '&review_ok=1');
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: ' . $back . '&review_error=' . urlencode('Gagal menyimpan ulasan.'));
    exit;
}
