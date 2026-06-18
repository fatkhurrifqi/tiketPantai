# TiketPantai - PHP + MySQL Backend

## Cara Deploy ke Server PHP + MySQL

### 1. Persiapan Server
Pastikan server Anda memiliki:
- PHP 7.4+ dengan ekstensi PDO MySQL
- MySQL 5.7+ atau MariaDB 10.3+
- Apache/Nginx web server

### 2. Setup Database
```bash
# Login ke MySQL
mysql -u root -p

# Import schema dan data
source database.sql
```

Atau jalankan via phpMyAdmin: import file `database.sql`

### 3. Konfigurasi Koneksi Database
Edit file `db.php`:
```php
$DB_HOST = '127.0.0.1';  // Host MySQL
$DB_NAME = 'tiketpantai'; // Nama database
$DB_USER = 'root';        // Username MySQL
$DB_PASS = '';            // Password MySQL
```

### 4. Buat User Admin
```bash
php seed.php
```
Atau import `database.sql` yang sudah termasuk data user default.

### 5. Upload ke Server
Upload semua file PHP ke document root server Anda:
```
/var/www/html/tiketpantai/
├── index.php
├── checkout.php
├── process_order.php
├── orders.php
├── db.php
├── database.sql
├── seed.php
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── admin/
│   └── index.php
├── beaches/           (folder gambar)
│   ├── pantai-klothok.png
│   ├── pantai-sembukan.png
│   └── karang-payung.png
└── assets/
    └── style.css
```

### 6. Akun Default
- **Admin**: admin@tiketpantai.com / admin123
- **User**: user@example.com / user123

## Fitur yang Tersedia

### Halaman Publik
- **Beranda** (`index.php`) - Daftar destinasi wisata
- **Checkout** (`checkout.php?wisata=slug`) - Pilih tiket & fasilitas
- **Konfirmasi** (`process_order.php`) - Proses pemesanan
- **Pesanan Saya** (`orders.php`) - Riwayat pesanan user

### Halaman Auth
- **Login** (`auth/login.php`) - Masuk ke akun
- **Register** (`auth/register.php`) - Daftar akun baru
- **Logout** (`auth/logout.php`) - Keluar dari akun

### Halaman Admin
- **Dashboard** (`admin/index.php`) - Statistik, kelola pesanan, user, destinasi
- Update status pesanan (Menunggu → Dibayar → Selesai / Dibatalkan)

### API Endpoints (untuk integrasi Next.js)
- `POST auth/register.php` - Registrasi user
- `POST auth/login.php` - Login
- `GET destinations.php` - Daftar destinasi
- `GET/POST orders.php` - Daftar/buat pesanan
- `PATCH orders_update.php?id=X` - Update status pesanan
- `GET admin/stats.php` - Statistik dashboard
- `GET admin/users.php` - Daftar pengguna
