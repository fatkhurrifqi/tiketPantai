-- =============================================
-- TiketPantai - MySQL Database Schema
-- Import file ini ke MySQL server Anda
-- =============================================

CREATE DATABASE IF NOT EXISTS tiketpantai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tiketpantai;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) DEFAULT NULL,
  role VARCHAR(20) DEFAULT 'user',
  phone VARCHAR(20) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Destinations
CREATE TABLE IF NOT EXISTS destinations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  image VARCHAR(500) DEFAULT NULL,
  location TEXT,
  rating DECIMAL(2,1) DEFAULT 0,
  reviews INT DEFAULT 0,
  open_hours VARCHAR(50) DEFAULT NULL,
  price INT DEFAULT 0,
  description TEXT,
  category VARCHAR(100) DEFAULT 'Obyek Wisata',
  is_popular BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Ticket Types
CREATE TABLE IF NOT EXISTS ticket_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  price INT NOT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  description TEXT,
  destination_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Orders
CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(50) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  destination_id INT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  total_amount INT NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  payment_method VARCHAR(50) DEFAULT NULL,
  payment_detail VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (destination_id) REFERENCES destinations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Order Items
CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  ticket_type_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  subtotal INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Reviews (ulasan destinasi)
CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  destination_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review_per_user (destination_id, user_id),
  FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA
-- =============================================

-- Admin user (password: admin123)
INSERT IGNORE INTO users (email, password_hash, name, role, phone) VALUES
('admin@tiketpantai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin TiketPantai', 'admin', '0857-2826-9876');

-- Demo user (password: user123)
INSERT IGNORE INTO users (email, password_hash, name, role, phone) VALUES
('user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'user', '0812-9476-1810');

-- Destinations
-- rating & reviews dimulai dari 0; akan bertambah otomatis saat ada ulasan (lihat review_save.php)
INSERT IGNORE INTO destinations (name, slug, image, location, rating, reviews, open_hours, price, description, category, is_popular) VALUES
('Pantai Klothok', 'pantai-klothok', 'beaches/pantai-klothok.png', 'Dusun Kranding, Desa Paranggupito, Kec. Paranggupito, Kab. Wonogiri', 0, 0, '08:00 - 17:00', 15000, 'Menawarkan wajah baru berupa dermaga ikonik yang estetik, deburan ombak jernih, dan panorama tebing hijau yang asri.', 'Obyek Wisata', TRUE),
('Pantai Sembukan', 'pantai-sembukan', 'beaches/pantai-sembukan.png', 'Desa Paranggupito, Kec. Paranggupito, Kab. Wonogiri', 0, 0, '08:00 - 17:00', 15000, 'Destinasi petualangan alam yang eksotis dengan gugusan karang indah, area memancing, serta puncak bukit untuk melihat sunset.', 'Obyek Wisata', TRUE),
('Karang Payung', 'karang-payung', 'beaches/karang-payung.png', 'Dusun Palem, Desa Gunturharjo, Kec. Paranggupito, Kab. Wonogiri', 0, 0, '08:00 - 17:00', 15000, 'Terkenal dengan dinding tebing batu karang raksasa tegak lurus yang megah, menyerupai payung pelindung alami di tepi laut.', 'Obyek Wisata', TRUE);

-- Ticket Types for Pantai Klothok (id=1)
INSERT IGNORE INTO ticket_types (name, price, unit, description, destination_id) VALUES
('Tiket Masuk Pantai', 15000, '/orang', NULL, 1),
('Sewa Tenda Pantai', 10000, '/jam', 'Tenda ukuran sedang untuk 4 orang', 1),
('Sewa Tikar', 5000, '/jam', NULL, 1),
('Sewa Kursi Lipat', 5000, '/jam', NULL, 1),
('Sewa Meja Lipat', 5000, '/jam', NULL, 1),
('Sewa Tripod Foto', 5000, '/jam', NULL, 1),
('Ban Renang', 10000, '/hari', 'Ban renang untuk dewasa & anak', 1);

-- Ticket Types for Pantai Sembukan (id=2)
INSERT IGNORE INTO ticket_types (name, price, unit, description, destination_id) VALUES
('Tiket Masuk Pantai', 15000, '/orang', NULL, 2),
('Sewa Tenda Pantai', 10000, '/jam', 'Tenda ukuran sedang untuk 4 orang', 2),
('Sewa Tikar', 5000, '/jam', NULL, 2),
('Sewa Kursi Lipat', 5000, '/jam', NULL, 2),
('Sewa Meja Lipat', 5000, '/jam', NULL, 2),
('Sewa Tripod Foto', 5000, '/jam', NULL, 2),
('Sewa Alat Snorkeling', 25000, '/hari', 'Set lengkap: masker, snorkel, fin', 2);

-- Ticket Types for Karang Payung (id=3)
INSERT IGNORE INTO ticket_types (name, price, unit, description, destination_id) VALUES
('Tiket Masuk Pantai', 15000, '/orang', NULL, 3),
('Sewa Tenda Pantai', 10000, '/jam', 'Tenda ukuran sedang untuk 4 orang', 3),
('Sewa Tikar', 5000, '/jam', NULL, 3),
('Sewa Kursi Lipat', 5000, '/jam', NULL, 3),
('Sewa Meja Lipat', 5000, '/jam', NULL, 3),
('Sewa Tripod Foto', 5000, '/jam', NULL, 3),
('Tour Guide', 50000, '/sesi', 'Pemandu wisata lokal berpengalaman', 3);

-- =============================================
-- MIGRASI (jalankan HANYA jika database sudah pernah di-import)
-- Tambah kolom payment_detail pada tabel orders untuk menyimpan
-- bank/e-wallet spesifik yang dipilih user (mis. 'bca', 'gopay', 'qris')
-- =============================================
-- ALTER TABLE orders ADD COLUMN payment_detail VARCHAR(100) DEFAULT NULL AFTER payment_method;
