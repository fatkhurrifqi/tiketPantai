<?php
session_start();
$pdo = require __DIR__ . '/db.php';
$user = $_SESSION['user'] ?? null;

// Helper metode pembayaran
require __DIR__ . '/payments.php';

if (!$user) {
    header('Location: auth/login.php');
    exit;
}

// Fetch user orders
$stmt = $pdo->prepare('
    SELECT o.*, d.name as dest_name, d.location as dest_location, d.image as dest_image
    FROM orders o
    JOIN destinations d ON o.destination_id = d.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

// Get order items for each order
foreach ($orders as &$order) {
    $itemStmt = $pdo->prepare('
        SELECT oi.*, tt.name as tt_name, tt.price as tt_price
        FROM order_items oi
        JOIN ticket_types tt ON oi.ticket_type_id = tt.id
        WHERE oi.order_id = ?
    ');
    $itemStmt->execute([$order['id']]);
    $order['items'] = $itemStmt->fetchAll();
}
unset($order);

$statusConfig = [
    'pending' => ['color' => 'bg-amber-100 text-amber-700', 'icon' => 'fa-clock', 'label' => 'Menunggu'],
    'paid' => ['color' => 'bg-teal-100 text-teal-700', 'icon' => 'fa-check', 'label' => 'Dibayar'],
    'completed' => ['color' => 'bg-emerald-100 text-emerald-700', 'icon' => 'fa-check-double', 'label' => 'Selesai'],
    'cancelled' => ['color' => 'bg-red-100 text-red-700', 'icon' => 'fa-xmark', 'label' => 'Dibatalkan'],
];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pesanan Saya - TiketPantai</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css">
  <style>
  body { font-family: 'Inter', sans-serif; }
  h1, h2, h3, .font-display { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50">
  <nav class="tp-nav sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
      <a href="index.php" class="flex items-center gap-2">
        <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center"><i class="fa-solid fa-water text-white text-sm"></i></div>
        <span class="text-xl font-bold text-gray-900">tiket<span class="text-teal-500">Pantai</span></span>
      </a>
      <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali</a>
    </div>
  </nav>

  <div class="max-w-5xl mx-auto px-4 py-8">
    <div class="reveal mb-8">
      <h1 class="font-display text-3xl font-extrabold text-gray-900 mb-2">Pesanan Saya</h1>
      <p class="text-sm text-gray-500">Riwayat dan status pemesanan tiket Anda</p>
    </div>

    <?php if (empty($orders)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
      <i class="fa-solid fa-cart-shopping text-4xl text-gray-300 mb-4"></i>
      <h3 class="font-bold text-gray-900 mb-1">Belum Ada Pesanan</h3>
      <p class="text-sm text-gray-500">Anda belum memiliki riwayat pemesanan tiket.</p>
      <a href="index.php" class="tp-btn tp-btn-gradient inline-block mt-4 text-white px-6 py-2 rounded-xl text-sm font-semibold">Cari Tiket</a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($orders as $order):
        $sc = $statusConfig[$order['status']] ?? $statusConfig['pending'];
        $pi = resolve_payment($order['payment_method'] ?? null, $order['payment_detail'] ?? null);
      ?>
      <div class="reveal tp-card bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <span class="text-sm font-mono text-gray-400"><?= $order['order_number'] ?></span>
              <span class="<?= $sc['color'] ?> text-[10px] font-semibold px-2 py-0.5 rounded"><i class="fa-solid <?= $sc['icon'] ?> mr-1"></i><?= $sc['label'] ?></span>
            </div>
            <h3 class="font-bold text-gray-900 text-lg mb-1"><?= htmlspecialchars($order['dest_name']) ?></h3>
            <div class="flex flex-wrap gap-4 text-xs text-gray-500">
              <span><i class="fa-regular fa-calendar mr-1"></i><?= date('d F Y', strtotime($order['visit_date'])) ?></span>
              <?php if ($order['payment_method']): ?>
              <span><i class="fa-solid fa-credit-card mr-1"></i><?= htmlspecialchars($order['payment_method']) ?><?= !empty($pi['provider']) ? ' &middot; ' . htmlspecialchars($pi['provider']['name']) : '' ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="text-right">
            <div class="text-lg font-bold text-teal-600">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></div>
          </div>
        </div>
        <?php if (!empty($order['items'])): ?>
        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-2">
          <?php foreach ($order['items'] as $item): ?>
          <div class="flex justify-between text-xs text-gray-500">
            <span><?= htmlspecialchars($item['tt_name']) ?> x<?= $item['quantity'] ?></span>
            <span class="font-medium text-gray-700">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($order['status'] === 'pending'): ?>
        <div class="mt-4 bg-amber-50 rounded-xl p-3">
          <div class="text-xs font-semibold text-amber-800 mb-2"><i class="fa-solid fa-circle-info mr-1"></i>Instruksi Pembayaran</div>
          <?php if ($pi['type'] === 'bank' && !empty($pi['provider'])): ?>
            <div class="text-xs text-gray-700">
              Transfer ke <strong><?= htmlspecialchars($pi['provider']['name']) ?></strong>:
              <span class="font-mono font-bold text-teal-600"><?= htmlspecialchars($pi['provider']['number']) ?></span>
              <span class="text-gray-500">(a.n. <?= htmlspecialchars($pi['provider']['holder']) ?>)</span>
            </div>
          <?php elseif ($pi['type'] === 'ewallet' && !empty($pi['provider'])): ?>
            <div class="text-xs text-gray-700">
              Kirim ke <strong><?= htmlspecialchars($pi['provider']['name']) ?></strong>:
              <span class="font-mono font-bold text-teal-600"><?= htmlspecialchars($pi['provider']['number']) ?></span>
              <span class="text-gray-500">(a.n. <?= htmlspecialchars($pi['provider']['holder']) ?>)</span>
            </div>
          <?php elseif ($pi['type'] === 'qris'): ?>
            <div class="flex items-center gap-3">
              <img src="<?= htmlspecialchars($pi['image']) ?>" alt="QRIS" class="w-20 h-20 rounded-lg border border-gray-200 bg-white object-contain p-1">
              <span class="text-xs text-gray-700">Scan QRIS untuk membayar.</span>
            </div>
          <?php elseif ($pi['type'] === 'location'): ?>
            <div class="text-xs text-gray-700">Bayar langsung di lokasi destinasi saat kunjungan.</div>
          <?php else: ?>
            <div class="text-xs text-gray-500"><?= htmlspecialchars($order['payment_method'] ?? 'Metode tidak diketahui') ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <script src="assets/app.js"></script>
</body>
</html>
