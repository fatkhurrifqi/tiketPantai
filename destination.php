<?php
session_start();
$pdo = require __DIR__ . '/db.php';
$user = $_SESSION['user'] ?? null;
$slug = $_GET['wisata'] ?? '';

// Ambil destinasi
$stmt = $pdo->prepare('SELECT * FROM destinations WHERE slug = ? AND is_active = TRUE LIMIT 1');
$stmt->execute([$slug]);
$dest = $stmt->fetch();

if (!$dest) {
    die('Destinasi tidak ditemukan. <a href="index.php">Kembali</a>');
}

// Ambil ulasan + nama user
$revStmt = $pdo->prepare('
    SELECT r.*, u.name AS user_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.destination_id = ?
    ORDER BY r.created_at DESC
');
$revStmt->execute([$dest['id']]);
$reviews = $revStmt->fetchAll();

// Jumlah ulasan untuk daftar (jumlah teks yang ditampilkan)
$reviewCount = count($reviews);
// Rating & jumlah aggregate destinasi (diperbarui otomatis tiap ada ulasan)
$aggRating = (float)$dest['rating'];
$aggCount  = (int)$dest['reviews'];

// Ulasan milik user yang login (untuk pre-fill form)
$myReview = null;
if ($user) {
    $myStmt = $pdo->prepare('SELECT * FROM reviews WHERE destination_id = ? AND user_id = ?');
    $myStmt->execute([$dest['id'], $user['id']]);
    $myReview = $myStmt->fetch();
}

// Render bintang read-only
function render_stars($rating): string
{
    $rating = (int)round($rating);
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fa-solid fa-star ' . ($i <= $rating ? 'text-amber-400' : 'text-gray-200') . '"></i>';
    }
    return $html;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($dest['name']) ?> - TiketPantai</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css">
  <style>
  body { font-family: 'Inter', sans-serif; }
  h1, h2, h3, .font-display { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

  <!-- Navbar -->
  <nav class="tp-nav sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
      <a href="index.php" class="flex items-center gap-2">
        <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center"><i class="fa-solid fa-water text-white text-sm"></i></div>
        <span class="text-xl font-bold text-gray-900">tiket<span class="text-teal-500">Pantai</span></span>
      </a>
      <div class="flex items-center gap-3">
        <?php if ($user): ?>
        <a href="orders.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 px-3 py-1.5 rounded-lg">Pesanan Saya</a>
        <div class="flex items-center gap-2 bg-teal-50 px-3 py-1.5 rounded-full">
          <div class="w-6 h-6 bg-teal-500 rounded-full flex items-center justify-center"><span class="text-[10px] font-bold text-white"><?= strtoupper(substr($user['name'], 0, 1)) ?></span></div>
          <span class="text-sm font-medium text-teal-700"><?= htmlspecialchars($user['name']) ?></span>
        </div>
        <a href="auth/logout.php" class="text-sm text-gray-500 hover:text-red-500 px-3 py-1.5 border rounded-lg">Keluar</a>
        <?php else: ?>
        <a href="auth/login.php" class="text-sm font-medium text-gray-700 px-4 py-2 border rounded-xl">Masuk</a>
        <a href="auth/register.php" class="text-sm font-medium text-white bg-teal-500 hover:bg-teal-600 px-4 py-2 rounded-xl">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700 mb-6 inline-flex items-center gap-1"><i class="fa-solid fa-arrow-left"></i> Kembali</a>

    <!-- Notifikasi ulasan -->
    <?php if (isset($_GET['review_ok'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl mb-6"><i class="fa-solid fa-check mr-1"></i> Terima kasih! Ulasan Anda telah disimpan.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['review_error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-6"><i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($_GET['review_error']) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Info destinasi -->
      <div class="lg:col-span-2">
        <div class="reveal bg-white rounded-3xl shadow-md overflow-hidden">
          <div class="tp-zoom relative h-72 sm:h-[22rem]">
            <img src="<?= htmlspecialchars($dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
            <span class="absolute top-4 left-4 tp-badge-glass text-white text-xs font-medium px-3 py-1 rounded-full"><?= htmlspecialchars($dest['category']) ?></span>
            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
              <h1 class="font-display text-3xl sm:text-4xl font-extrabold drop-shadow-lg mb-1.5"><?= htmlspecialchars($dest['name']) ?></h1>
              <div class="flex items-center gap-1.5 text-sm text-white/90"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($dest['location']) ?></div>
            </div>
          </div>
          <div class="p-6">
            <div class="flex flex-wrap gap-4 text-sm text-gray-500 mb-4">
              <span class="flex items-center gap-1.5"><i class="fa-solid fa-star text-amber-400"></i>
              <span class="flex items-center gap-1.5"><i class="fa-solid fa-star text-amber-400"></i>
                <?php if ($dest['reviews'] > 0): ?>
                <strong class="text-gray-700"><?= $dest['rating'] ?></strong> (<?= $dest['reviews'] ?> ulasan)
                <?php else: ?>
                <span class="text-gray-400">Belum ada ulasan</span>
                <?php endif; ?>
              </span>
              <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-gray-400"></i> <?= htmlspecialchars($dest['open_hours']) ?></span>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed mb-6"><?= nl2br(htmlspecialchars($dest['description'])) ?></p>
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100">
              <div>
                <div class="text-[11px] text-gray-400">Mulai dari</div>
                <div class="text-xl font-bold text-teal-600">Rp <?= number_format($dest['price'], 0, ',', '.') ?></div>
              </div>
              <a href="checkout.php?wisata=<?= urlencode($dest['slug']) ?>" class="tp-btn tp-btn-gradient text-white text-sm font-semibold py-2.5 px-6 rounded-xl flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-ticket"></i> Beli Tiket
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Ringkasan ulasan + form -->
      <div>
        <div class="reveal bg-white rounded-2xl shadow-md p-6 sticky top-24">
          <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2"><i class="fa-solid fa-star text-amber-400"></i> Ulasan Pengunjung</h2>
          <div class="flex items-end gap-2 mb-1">
            <span class="text-4xl font-bold text-gray-900"><?= $aggCount > 0 ? number_format($aggRating, 1) : '-' ?></span>
            <span class="text-sm text-gray-400 mb-1">/ 5</span>
          </div>
          <div class="text-base mb-1"><?= render_stars($aggRating) ?></div>
          <p class="text-xs text-gray-500 mb-5">Berdasarkan <?= $aggCount ?> ulasan</p>

          <?php if ($user): ?>
          <form method="POST" action="review_save.php" class="space-y-3">
            <input type="hidden" name="destination_id" value="<?= $dest['id'] ?>">
            <div>
              <label class="block text-xs text-gray-500 mb-1.5 font-medium"><?= $myReview ? 'Ubah rating ulasanmu' : 'Beri rating' ?></label>
              <div class="flex items-center gap-1 text-2xl" id="starPicker">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="star-btn <?= $i <= ($myReview['rating'] ?? 0) ? 'text-amber-400' : 'text-gray-300' ?> hover:scale-110 transition cursor-pointer" onclick="setRating(<?= $i ?>)"><i class="fa-solid fa-star"></i></button>
                <?php endfor; ?>
                <input type="hidden" name="rating" id="ratingInput" value="<?= (int)($myReview['rating'] ?? 0) ?>">
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1.5 font-medium">Ulasan</label>
              <textarea name="comment" rows="3" placeholder="Bagikan pengalamanmu..." class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"><?= htmlspecialchars($myReview['comment'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="w-full bg-teal-500 hover:bg-teal-600 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
              <i class="fa-solid fa-paper-plane mr-1"></i> <?= $myReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' ?>
            </button>
          </form>
          <?php else: ?>
          <div class="text-center py-4 border-t border-gray-100">
            <i class="fa-solid fa-pen-to-square text-2xl text-gray-300 mb-2"></i>
            <p class="text-xs text-gray-500 mb-3">Masuk untuk memberi ulasan</p>
            <a href="auth/login.php" class="inline-block bg-teal-500 hover:bg-teal-600 text-white text-sm font-semibold px-5 py-2 rounded-xl">Masuk</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Daftar ulasan -->
    <div class="reveal mt-8">
      <h2 class="text-lg font-bold text-gray-900 mb-4"><i class="fa-solid fa-comments mr-2 text-teal-500"></i>Semua Ulasan (<?= $reviewCount ?>)</h2>
      <?php if (empty($reviews)): ?>
      <div class="bg-white rounded-2xl shadow-sm p-10 text-center">
        <i class="fa-solid fa-comment-dots text-4xl text-gray-300 mb-3"></i>
        <p class="text-sm text-gray-500">Belum ada ulasan. Jadilah yang pertama memberi ulasan!</p>
      </div>
      <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($reviews as $r): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-teal-500 rounded-full flex items-center justify-center"><span class="text-xs font-bold text-white"><?= strtoupper(substr($r['user_name'], 0, 1)) ?></span></div>
              <div>
                <div class="text-sm font-semibold text-gray-900">
                  <?= htmlspecialchars($r['user_name']) ?>
                  <?php if ($user && $r['user_id'] == $user['id']): ?>
                  <span class="text-[10px] bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded ml-1">Anda</span>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-gray-400"><?= date('d F Y', strtotime($r['created_at'])) ?></div>
              </div>
            </div>
            <div class="text-sm"><?= render_stars($r['rating']) ?></div>
          </div>
          <?php if ($r['comment']): ?>
          <p class="text-sm text-gray-600 leading-relaxed mt-2"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    let currentRating = <?= (int)($myReview['rating'] ?? 0) ?>;
    function renderStars() {
      document.querySelectorAll('.star-btn').forEach(function (s, i) {
        s.classList.toggle('text-amber-400', i < currentRating);
        s.classList.toggle('text-gray-300', i >= currentRating);
      });
    }
    function setRating(n) {
      currentRating = n;
      document.getElementById('ratingInput').value = n;
      renderStars();
    }
  </script>
  <script src="assets/app.js"></script>
</body>
</html>
