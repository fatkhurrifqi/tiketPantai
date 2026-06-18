<?php
session_start();
$pdo = require __DIR__ . '/../db.php';
require __DIR__ . '/../payments.php';
$user = $_SESSION['user'] ?? null;

// Prefix path asset (admin/ 1 level di bawah root project)
$ASSET = '../';

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$destinationId = (int)($_GET['destination_id'] ?? 0);

$destStmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
$destStmt->execute([$destinationId]);
$dest = $destStmt->fetch();

if (!$dest) {
    header('Location: index.php');
    exit;
}

// Ambil daftar tiket/fasilitas untuk destinasi ini
$ttStmt = $pdo->prepare('SELECT * FROM ticket_types WHERE destination_id = ? ORDER BY price ASC, id ASC');
$ttStmt->execute([$destinationId]);
$ticketTypes = $ttStmt->fetchAll();

// Peta pesan notifikasi
$messages = [
    'ticket_created' => ['type' => 'success', 'text' => 'Tiket/fasilitas berhasil ditambahkan.'],
    'ticket_updated' => ['type' => 'success', 'text' => 'Tiket/fasilitas berhasil diperbarui.'],
    'ticket_deleted' => ['type' => 'success', 'text' => 'Tiket/fasilitas berhasil dihapus.'],
    'ticket_invalid' => ['type' => 'error',   'text' => 'Data tidak lengkap (nama wajib diisi).'],
    'ticket_in_use'  => ['type' => 'error',   'text' => 'Tiket/fasilitas tidak bisa dihapus karena sudah ada yang memesan.'],
    'ticket_error'   => ['type' => 'error',   'text' => 'Terjadi kesalahan saat menyimpan tiket/fasilitas.'],
];

// Data tiket untuk form edit (JS)
$ttJson = [];
foreach ($ticketTypes as $t) {
    $ttJson[] = [
        'id'          => (int)$t['id'],
        'name'        => $t['name'],
        'price'       => (int)$t['price'],
        'unit'        => $t['unit'],
        'description' => $t['description'],
    ];
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Tiket - <?= htmlspecialchars($dest['name']) ?> - TiketPantai</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>body { font-family: 'Inter', sans-serif; }</style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200/60 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
      <a href="index.php" class="flex items-center gap-2">
        <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center"><i class="fa-solid fa-water text-white text-sm"></i></div>
        <span class="text-xl font-bold text-gray-900">tiket<span class="text-teal-500">Pantai</span></span>
      </a>
      <div class="flex items-center gap-3">
        <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left mr-1"></i> Dashboard</a>
        <span class="text-sm font-medium text-teal-700 bg-teal-50 px-3 py-1 rounded-full"><i class="fa-solid fa-shield-halved mr-1"></i>Admin</span>
      </div>
    </div>
  </nav>

  <div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <div class="mb-6">
      <div class="flex items-center gap-2 text-sm">
        <img src="<?= $ASSET . htmlspecialchars($dest['image'] ?: 'assets/no-image.svg') ?>" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Kelola Tiket &amp; Fasilitas</h1>
          <p class="text-sm text-gray-500"><?= htmlspecialchars($dest['name']) ?></p>
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-2">Atur jenis tiket masuk dan fasilitas sewa beserta harganya untuk destinasi ini. Tiap pantai bisa punya fasilitas yang berbeda.</p>
    </div>

    <?php
    $msgCode = $_GET['msg'] ?? '';
    if ($msgCode !== '' && isset($messages[$msgCode])):
        $m = $messages[$msgCode];
        $bg = $m['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700';
        $icon = $m['type'] === 'success' ? 'fa-check' : 'fa-circle-exclamation';
    ?>
    <div class="<?= $bg ?> border text-sm px-4 py-3 rounded-xl mb-6">
      <i class="fa-solid <?= $icon ?> mr-1"></i> <?= htmlspecialchars($m['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Daftar tiket -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
      <div class="p-6 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-900"><i class="fa-solid fa-ticket mr-2 text-teal-500"></i>Daftar Tiket &amp; Fasilitas</h2>
          <p class="text-xs text-gray-400 mt-0.5"><?= count($ticketTypes) ?> item terdaftar</p>
        </div>
        <button onclick="openTicketForm(null)" class="bg-teal-500 hover:bg-teal-600 text-white text-sm font-semibold px-4 py-2 rounded-xl flex items-center gap-2">
          <i class="fa-solid fa-plus"></i> Tambah
        </button>
      </div>

      <?php if (empty($ticketTypes)): ?>
      <div class="p-12 text-center">
        <i class="fa-solid fa-ticket text-4xl text-gray-300 mb-3"></i>
        <p class="text-sm text-gray-500">Belum ada tiket/fasilitas. Klik <strong>Tambah</strong> untuk membuat.</p>
      </div>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Nama</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Harga</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Satuan</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Keterangan</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($ticketTypes as $t): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 font-medium"><?= htmlspecialchars($t['name']) ?></td>
              <td class="px-6 py-3 font-semibold text-teal-600">Rp <?= number_format($t['price'], 0, ',', '.') ?></td>
              <td class="px-6 py-3 text-xs text-gray-500"><?= htmlspecialchars($t['unit'] ?: '-') ?></td>
              <td class="px-6 py-3 text-xs text-gray-500 max-w-[260px]"><?= htmlspecialchars($t['description'] ?: '-') ?></td>
              <td class="px-6 py-3">
                <div class="flex items-center gap-1.5">
                  <button onclick="openTicketForm(<?= (int)$t['id'] ?>)" class="text-xs border border-gray-200 px-2.5 py-1.5 rounded-lg hover:bg-gray-50" title="Edit"><i class="fa-solid fa-pen"></i></button>
                  <button onclick="confirmDeleteTicket(<?= (int)$t['id'] ?>, '<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>')" class="text-xs border border-red-200 text-red-600 px-2.5 py-1.5 rounded-lg hover:bg-red-50" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
  </div>

  <!-- Form hapus (disubmit via JS) -->
  <form id="deleteTicketForm" method="POST" action="ticket_save.php" class="hidden">
    <input type="hidden" name="action" value="ticket_delete">
    <input type="hidden" name="destination_id" value="<?= $destinationId ?>">
    <input type="hidden" name="id" id="deleteTicketId" value="">
  </form>

  <!-- Modal Tambah/Edit Tiket -->
  <div id="ticketModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeTicketModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
      <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
        <h3 class="font-bold text-gray-900" id="ticketModalTitle"><i class="fa-solid fa-ticket mr-2 text-teal-500"></i>Tambah Tiket/Fasilitas</h3>
        <button type="button" onclick="closeTicketModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="ticketForm" method="POST" action="ticket_save.php" class="p-5 space-y-4">
        <input type="hidden" name="action" value="ticket_create">
        <input type="hidden" name="destination_id" value="<?= $destinationId ?>">
        <input type="hidden" name="id" value="">
        <div>
          <label class="block text-xs text-gray-500 mb-1 font-medium">Nama Tiket/Fasilitas *</label>
          <input type="text" name="name" required placeholder="contoh: Tiket Masuk Pantai / Sewa Tenda" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1 font-medium">Harga (Rp) *</label>
            <input type="number" name="price" min="0" value="0" required class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1 font-medium">Satuan</label>
            <input type="text" name="unit" placeholder="/orang, /jam, /hari" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1 font-medium">Keterangan</label>
          <textarea name="description" rows="2" placeholder="Opsional, contoh: Tenda untuk 4 orang" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
          <button type="button" onclick="closeTicketModal()" class="border border-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-50">Batal</button>
          <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const TICKETS = <?= json_encode($ttJson, JSON_UNESCAPED_SLASHES) ?>;
    // 'name','action','id' adalah properti form bawaan -> pakai elements.namedItem
    function field(form, name) { return form.elements.namedItem(name); }

    function openTicketForm(id) {
      const form = document.getElementById('ticketForm');
      form.reset();
      if (id) {
        const t = TICKETS.find(function (x) { return x.id === id; });
        if (!t) return;
        field(form, 'action').value = 'ticket_update';
        field(form, 'id').value = t.id;
        field(form, 'name').value = t.name;
        field(form, 'price').value = t.price;
        field(form, 'unit').value = t.unit || '';
        field(form, 'description').value = t.description || '';
        document.getElementById('ticketModalTitle').innerHTML = '<i class="fa-solid fa-pen mr-2 text-teal-500"></i>Edit Tiket/Fasilitas';
      } else {
        field(form, 'action').value = 'ticket_create';
        field(form, 'id').value = '';
        document.getElementById('ticketModalTitle').innerHTML = '<i class="fa-solid fa-plus mr-2 text-teal-500"></i>Tambah Tiket/Fasilitas';
      }
      document.getElementById('ticketModal').classList.remove('hidden');
    }
    function closeTicketModal() { document.getElementById('ticketModal').classList.add('hidden'); }

    function confirmDeleteTicket(id, name) {
      if (confirm('Hapus tiket/fasilitas "' + name + '"?')) {
        document.getElementById('deleteTicketId').value = id;
        document.getElementById('deleteTicketForm').submit();
      }
    }
  </script>
</body>

</html>
