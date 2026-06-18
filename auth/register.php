<?php
session_start();
$pdo = require __DIR__ . '/../db.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$email || !$password) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, phone) VALUES (?, ?, ?, ?)');
            $insert->execute([$name, $email, $passwordHash, $phone ?: null]);
            $id = $pdo->lastInsertId();
            $_SESSION['user'] = ['id' => $id, 'email' => $email, 'name' => $name, 'role' => 'user', 'phone' => $phone ?: null];
            header('Location: ../index.php');
            exit;
        }
    }
}

$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar - TiketPantai</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/app.css">
  <style>
  body { font-family: 'Inter', sans-serif; }
  h1, h2, h3, .font-display { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
  <nav class="tp-nav bg-white/80 backdrop-blur-lg border-b border-gray-200/60">
    <div class="max-w-7xl mx-auto px-4 flex items-center h-16">
      <a href="../index.php" class="flex items-center gap-2">
        <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center"><i class="fa-solid fa-water text-white text-sm"></i></div>
        <span class="text-xl font-bold text-gray-900">tiket<span class="text-teal-500">Pantai</span></span>
      </a>
    </div>
  </nav>

  <main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="reveal grid grid-cols-1 md:grid-cols-2 max-w-5xl w-full bg-white rounded-3xl shadow-xl overflow-hidden">

      <!-- Branding panel -->
      <div class="tp-hero relative hidden md:flex flex-col justify-between p-10 text-white">
        <div class="relative">
          <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mb-8"><i class="fa-solid fa-water text-xl"></i></div>
          <h2 class="font-display text-3xl font-extrabold leading-tight mb-3">Gabung &amp; Mulai<br>Petualanganmu</h2>
          <p class="text-white/85 text-sm leading-relaxed max-w-xs">Buat akun untuk memesan tiket, menyimpan riwayat, dan memberi ulasan destinasi favoritmu.</p>
        </div>
        <ul class="relative space-y-3 text-sm text-white/90">
          <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-cyan-200"></i> Gratis, tanpa biaya</li>
          <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-cyan-200"></i> Kelola pesanan dengan mudah</li>
          <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-cyan-200"></i> Akses promo &amp; ulasan</li>
        </ul>
      </div>

      <!-- Form -->
      <div class="p-8 sm:p-10">
        <h2 class="font-display text-2xl font-extrabold text-gray-900 mb-1">Buat Akun Baru</h2>
        <p class="text-sm text-gray-500 mb-6">Hanya butuh beberapa detik untuk mendaftar.</p>

        <?php if ($errors): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded mb-4">
          <?php foreach ($errors as $e): ?>
          <p class="text-red-700 text-sm"><?= htmlspecialchars($e) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($user): ?>
        <div class="text-center">
          <p class="text-gray-600">Anda sudah masuk sebagai <strong><?= htmlspecialchars($user['name']) ?></strong>.</p>
          <a href="logout.php" class="tp-btn inline-block mt-4 bg-gray-200 text-gray-700 px-6 py-2 rounded-xl text-sm font-medium">Keluar</a>
        </div>
        <?php else: ?>
        <form method="post" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Lengkap</label>
            <input type="text" name="name" required placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($name ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
              <input type="email" name="email" required placeholder="nama@email.com" value="<?= htmlspecialchars($email ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">No. Telepon</label>
              <input type="text" name="phone" placeholder="08xx-xxxx-xxxx" value="<?= htmlspecialchars($phone ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
              <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Konfirmasi</label>
              <input type="password" name="password_confirm" required placeholder="Ulangi password" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
          </div>
          <button type="submit" class="tp-btn tp-btn-gradient w-full text-white py-3 rounded-xl font-semibold text-sm">Daftar Sekarang</button>
        </form>
        <p class="text-center text-sm text-gray-500 mt-4">Sudah punya akun? <a href="login.php" class="text-teal-600 font-semibold hover:underline">Masuk di sini</a></p>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script src="../assets/app.js"></script>
</body>
</html>
