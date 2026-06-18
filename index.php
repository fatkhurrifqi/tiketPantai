<?php
session_start();
$pdo = require __DIR__ . '/db.php';
$title = 'Home';
$user = $_SESSION['user'] ?? null;

// Fetch destinations
$stmt = $pdo->query('SELECT * FROM destinations WHERE is_active = TRUE ORDER BY created_at DESC');
$destinations = $stmt->fetchAll();

// Fetch ticket types for each destination
foreach ($destinations as &$dest) {
    $ttStmt = $pdo->prepare('SELECT * FROM ticket_types WHERE destination_id = ? ORDER BY price ASC');
    $ttStmt->execute([$dest['id']]);
    $dest['ticket_types'] = $ttStmt->fetchAll();
}
unset($dest);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?> - TiketPantai</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css">
  <style>
  body {
    font-family: 'Inter', sans-serif;
  }

  h1,
  h2,
  h3,
  .font-display {
    font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  }
  </style>
</head>

<body class="bg-gray-50 font-sans antialiased">

  <!-- Navbar -->
  <nav class="tp-nav sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="flex items-center justify-between h-16">
        <a href="index.php" class="flex items-center gap-2 hover:opacity-80 transition">
          <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-water text-white text-sm"></i>
          </div>
          <span class="text-xl font-bold text-gray-900">tiket<span class="text-teal-500">Pantai</span></span>
        </a>
        <div class="hidden md:flex items-center gap-1">
          <a href="index.php" class="px-3 py-1.5 text-sm font-medium text-teal-700 bg-teal-50 rounded-lg">Beranda</a>
          <?php if ($user): ?>
          <a href="orders.php"
            class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg">Pesanan Saya</a>
          <?php if ($user['role'] === 'admin'): ?>
          <a href="admin/index.php"
            class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg"><i
              class="fa-solid fa-shield-halved mr-1"></i>Admin</a>
          <?php endif; ?>
          <?php endif; ?>
        </div>
        <div class="hidden md:flex items-center gap-3">
          <?php if ($user): ?>
          <div class="flex items-center gap-2 bg-teal-50 px-3 py-1.5 rounded-full">
            <div class="w-6 h-6 bg-teal-500 rounded-full flex items-center justify-center">
              <span class="text-[10px] font-bold text-white"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
            </div>
            <span class="text-sm font-medium text-teal-700"><?= htmlspecialchars($user['name']) ?></span>
            <?php if ($user['role'] === 'admin'): ?>
            <span class="bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-bold">ADMIN</span>
            <?php endif; ?>
          </div>
          <a href="auth/logout.php"
            class="text-sm text-gray-500 hover:text-red-500 px-3 py-1.5 border rounded-lg">Keluar</a>
          <?php else: ?>
          <a href="auth/login.php" class="text-sm font-medium text-gray-700 px-4 py-2 border rounded-xl">Masuk</a>
          <a href="auth/register.php"
            class="text-sm font-medium text-white bg-teal-500 hover:bg-teal-600 px-4 py-2 rounded-xl">Daftar</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="relative overflow-hidden text-white">
    <!-- Background slideshow (foto destinasi, autoplay) -->
    <div class="tp-slides">
      <?php foreach ($destinations as $i => $dest): ?>
      <div class="tp-slide <?= $i === 0 ? 'is-active' : '' ?>"
        style="background-image: url('<?= htmlspecialchars($dest['image']) ?>');"></div>
      <?php endforeach; ?>
    </div>
    <!-- Overlay teal: teks tetap terbaca & selaras tema -->
    <div class="tp-hero-overlay absolute inset-0 z-[2]"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-28">
      <div class="max-w-2xl reveal is-visible">
        <span
          class="tp-badge-glass inline-flex items-center gap-1.5 text-white text-xs font-medium px-3.5 py-1.5 rounded-full mb-5">
          <i class="fa-solid fa-sun text-amber-300 text-[11px]"></i> E-Tiketing Paranggupito
        </span>
        <h1 class="font-display text-4xl sm:text-6xl font-extrabold leading-[1.05] mb-5 tracking-tight">
          Jelajahi Pesona<br><span
            class="bg-clip-text text-transparent bg-gradient-to-r from-white via-cyan-100 to-teal-100">Pantai
            Paranggupito</span>
        </h1>
        <p class="text-base sm:text-lg text-white/85 mb-8 max-w-lg leading-relaxed">Platform resmi pembelian tiket
          wisata online
          Kecamatan Paranggupito, Kabupaten Wonogiri. Pesan dari rumah, nikmati pantainya.</p>

        <div class="flex flex-wrap items-center gap-3">
          <a href="#destinasi"
            class="tp-btn tp-btn-gradient inline-flex items-center gap-2 text-white text-sm font-semibold py-3 px-6 rounded-xl shadow-lg">
            <i class="fa-solid fa-compass"></i> Lihat Destinasi
          </a>
          <a href="#pesona"
            class="tp-btn inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/25 text-white text-sm font-semibold py-3 px-6 rounded-xl backdrop-blur-sm">
            <i class="fa-solid fa-play text-[11px]"></i> Selengkapnya
          </a>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-3 sm:gap-5 mt-14 max-w-xl reveal is-visible" style="--reveal-delay:120ms">
        <div class="tp-stat px-3 py-4 sm:px-5 sm:py-5 text-center">
          <div class="font-display text-2xl sm:text-4xl font-extrabold"><?= count($destinations) ?>+</div>
          <div class="text-white/75 text-[11px] sm:text-sm mt-0.5">Destinasi</div>
        </div>
        <div class="tp-stat px-3 py-4 sm:px-5 sm:py-5 text-center">
          <div class="font-display text-2xl sm:text-4xl font-extrabold">1.9K+</div>
          <div class="text-white/75 text-[11px] sm:text-sm mt-0.5">Ulasan</div>
        </div>
        <div class="tp-stat px-3 py-4 sm:px-5 sm:py-5 text-center">
          <div class="font-display text-2xl sm:text-4xl font-extrabold">4.6</div>
          <div class="text-white/75 text-[11px] sm:text-sm mt-0.5">Rating</div>
        </div>
      </div>

      <!-- Dots + caption slideshow -->
      <div class="mt-10 reveal is-visible">
        <div class="tp-dots" role="tablist" aria-label="Galeri pantai">
          <?php foreach ($destinations as $i => $dest): ?>
          <button type="button" class="tp-dot <?= $i === 0 ? 'is-active' : '' ?>"
            data-name="<?= htmlspecialchars($dest['name']) ?>"
            aria-label="Tampilkan <?= htmlspecialchars($dest['name']) ?>"></button>
          <?php endforeach; ?>
        </div>
        <p class="text-xs text-white/80 mt-3"><i class="fa-solid fa-camera-retro mr-1"></i><span
            class="tp-caption"><?= htmlspecialchars($destinations[0]['name'] ?? '') ?></span></p>
      </div>
    </div>
    <div class="absolute bottom-0 w-full leading-[0] z-10">
      <svg viewBox="0 0 1440 80" fill="none" preserveAspectRatio="none" class="w-full h-[40px] sm:h-[70px]">
        <path d="M0 80V40C240 70 480 10 720 40C960 70 1200 10 1440 40V80H0Z" fill="#f9fafb" />
      </svg>
    </div>
  </section>

  <!-- Destinations -->
  <section id="destinasi" class="max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20 scroll-mt-20">
    <div class="flex items-end justify-between mb-10 reveal">
      <div>
        <span class="inline-flex items-center gap-1.5 text-teal-600 text-xs font-bold tracking-wider mb-2"><i
            class="fa-solid fa-water"></i> DESTINASI</span>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-gray-900">Wisata Pantai Pilihan</h2>
        <p class="text-gray-500 text-sm mt-2">Temukan pantai eksotis di Paranggupito, Wonogiri</p>
      </div>
      <div class="hidden sm:flex gap-2">
        <button class="bg-teal-500 text-white px-4 py-1.5 rounded-full text-sm font-medium shadow-sm">Semua</button>
        <button
          class="bg-white text-gray-600 border border-gray-200 px-4 py-1.5 rounded-full text-sm font-medium hover:border-teal-300 hover:text-teal-600 transition">Obyek
          Wisata</button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
      <?php foreach ($destinations as $dest): ?>
      <div class="reveal tp-card bg-white rounded-3xl overflow-hidden shadow-md border border-gray-100">
        <a href="destination.php?wisata=<?= urlencode($dest['slug']) ?>" class="tp-zoom block relative h-56">
          <img src="<?= htmlspecialchars($dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>"
            class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent"></div>
          <div class="absolute top-3 left-3 flex gap-1.5">
            <span
              class="bg-emerald-500/90 text-white text-[10px] font-semibold px-2 py-0.5 rounded flex items-center gap-1">
              <span class="w-1.5 h-1.5 bg-white rounded-full"></span> Buka
            </span>
            <?php if ($dest['is_popular']): ?>
            <span
              class="bg-orange-400/90 text-white text-[10px] font-semibold px-2 py-0.5 rounded flex items-center gap-1">
              <i class="fa-solid fa-star text-[8px]"></i> Populer
            </span>
            <?php endif; ?>
          </div>
          <span
            class="absolute bottom-3 left-3 bg-black/40 backdrop-blur-sm text-white text-[10px] px-2 py-0.5 rounded"><?= htmlspecialchars($dest['category']) ?></span>
        </a>
        <div class="p-5">
          <h3 class="text-lg font-bold text-gray-900 mb-2"><a
              href="destination.php?wisata=<?= urlencode($dest['slug']) ?>"
              class="hover:text-teal-600 transition-colors"><?= htmlspecialchars($dest['name']) ?></a></h3>
          <div class="flex items-start gap-1.5 text-xs text-gray-500 mb-2">
            <i class="fa-solid fa-location-dot mt-0.5 text-gray-400 shrink-0"></i>
            <p class="line-clamp-2"><?= htmlspecialchars($dest['location']) ?></p>
          </div>
          <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-2">
            <i class="fa-solid fa-star text-amber-400"></i>
            <?php if ($dest['reviews'] > 0): ?>
            <span class="font-bold text-gray-700"><?= $dest['rating'] ?></span>
            <span class="text-gray-400">(<?= $dest['reviews'] ?> ulasan)</span>
            <?php else: ?>
            <span class="text-gray-400">Belum ada ulasan</span>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-4">
            <i class="fa-regular fa-clock text-gray-400"></i>
            <span><?= htmlspecialchars($dest['open_hours']) ?></span>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <div class="text-[10px] text-gray-400">Mulai dari</div>
              <div class="text-sm font-bold text-teal-600">Rp <?= number_format($dest['price'], 0, ',', '.') ?></div>
            </div>
            <a href="checkout.php?wisata=<?= urlencode($dest['slug']) ?>"
              class="tp-btn tp-btn-gradient text-white text-sm font-semibold py-2 px-4 rounded-xl flex items-center gap-1.5 shadow-sm">
              <i class="fa-solid fa-ticket"></i> Beli Tiket
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Features -->
  <section id="pesona" class="bg-white py-20 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 text-center">
      <div class="reveal">
        <span class="inline-flex items-center gap-1.5 text-teal-600 text-xs font-bold tracking-wider mb-2"><i
            class="fa-solid fa-star"></i> PESONA</span>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3">Keindahan yang Memikat</h2>
        <p class="text-gray-500 max-w-xl mx-auto mb-12">Karakteristik unik dari pantai-pantai eksotis di Kabupaten
          Wonogiri.</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
        <div
          class="reveal tp-card flex flex-col items-center text-center px-6 py-8 border border-gray-100 bg-gray-50/50 rounded-3xl">
          <div class="w-14 h-14 bg-teal-50 rounded-2xl shadow-sm flex items-center justify-center text-teal-600 mb-5"><i
              class="fa-solid fa-anchor text-xl"></i></div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Pantai Klotok</h3>
          <p class="text-sm text-gray-500 leading-relaxed">Dermaga ikonik yang estetik, deburan ombak jernih, dan
            panorama tebing hijau yang asri.</p>
        </div>
        <div
          class="reveal tp-card flex flex-col items-center text-center px-6 py-8 border border-gray-100 bg-gray-50/50 rounded-3xl">
          <div
            class="w-14 h-14 bg-emerald-50 rounded-2xl shadow-sm flex items-center justify-center text-emerald-600 mb-5">
            <i class="fa-solid fa-mountain text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Karang Payung</h3>
          <p class="text-sm text-gray-500 leading-relaxed">Dinding tebing batu karang raksasa yang megah, menyerupai
            payung pelindung alami di tepi laut.</p>
        </div>
        <div
          class="reveal tp-card flex flex-col items-center text-center px-6 py-8 border border-gray-100 bg-gray-50/50 rounded-3xl">
          <div class="w-14 h-14 bg-amber-50 rounded-2xl shadow-sm flex items-center justify-center text-amber-600 mb-5">
            <i class="fa-solid fa-compass text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Pantai Sembukan</h3>
          <p class="text-sm text-gray-500 leading-relaxed">Gugusan karang indah, area memancing, serta puncak bukit
            untuk melihat sunset.</p>
        </div>
        <div
          class="reveal tp-card flex flex-col items-center text-center px-6 py-8 border border-gray-100 bg-gray-50/50 rounded-3xl">
          <div class="w-14 h-14 bg-amber-50 rounded-2xl shadow-sm flex items-center justify-center text-amber-600 mb-5">
            <i class="fa-solid fa-map-location-dot"></i>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Pantai Kwaru</h3>
          <p class="text-sm text-gray-500 leading-relaxed">Air terjun alami yang jernih dan sejuk menjadi daya tarik
            tambahan yang membuat pantai Kwaru semakin memikat untuk dikunjungi.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-[#0b1325] text-[#a0aec0] text-xs mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mb-10">
        <div>
          <h3 class="text-white text-xl font-bold tracking-wide mb-3">tiket<span
              class="text-teal-400">Paranggupito</span></h3>
          <p class="text-[#8a99ad] leading-relaxed text-[13px]">Platform resmi pembelian tiket wisata online Kecamatan
            Paranggupito.</p>
        </div>
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <h5 class="text-white text-[11px] font-bold tracking-widest uppercase mb-4">Hubungi Kami</h5>
            <ul class="space-y-3 text-[13px]">
              <li class="flex items-start gap-2"><i
                  class="fa-solid fa-location-dot text-gray-500 mt-0.5"></i><span>Kabupaten Wonogiri, Jawa Tengah</span>
              </li>
              <li class="flex items-center gap-2"><i
                  class="fa-solid fa-envelope text-gray-500"></i><span>info@etiketingparanggupito.com</span></li>
            </ul>
          </div>
          <div>
            <h5 class="text-white text-[11px] font-bold tracking-widest uppercase mb-4">Layanan Pelanggan</h5>
            <ul class="space-y-2 text-[13px] mb-3">
              <li class="flex items-center gap-2"><i
                  class="fa-brands fa-whatsapp text-emerald-500"></i><span>0857-2826-9876</span></li>
              <li class="flex items-center gap-2"><i
                  class="fa-brands fa-whatsapp text-emerald-500"></i><span>0812-9476-1810</span></li>
            </ul>
          </div>
        </div>
      </div>
      <hr class="border-[#1e293b] my-6">
      <div
        class="flex flex-col md:flex-row md:justify-between items-center text-center md:text-left text-[#718096] text-[11px] gap-2">
        <p>&copy; <?= date('Y') ?> E-Tiketing Paranggupito. Seluruh hak cipta dilindungi.</p>
        <p class="text-gray-500">Dikelola oleh Pemerintah Kabupaten Wonogiri</p>
      </div>
    </div>
  </footer>
  <script src="assets/app.js"></script>
</body>

</html>