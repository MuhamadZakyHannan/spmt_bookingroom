-- Database Schema for MeetSpace Corporate Room Booking System
-- Compatibility: MySQL 5.7+ / MariaDB 10.2+ / XAMPP

CREATE DATABASE IF NOT EXISTS meetspace_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meetspace_db;

-- Table: users (Hanya 2 akun: 1 User, 1 Admin. Password untuk semua akun bawaan: password123)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: rooms
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    location VARCHAR(150) NOT NULL,
    floor VARCHAR(50) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    facilities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    user_dept VARCHAR(100) DEFAULT NULL,
    room_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT,
    activity_type VARCHAR(50) DEFAULT 'internal_divisi',
    attendees_count INT DEFAULT 1,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_bookings_room_date_status (room_id, date, status),
    INDEX idx_bookings_date_status (date, status),
    INDEX idx_bookings_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: notifications (Notifikasi booking pending per akun Administrator)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id INT NOT NULL,
    booking_id INT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'booking_pending',
    title VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    UNIQUE KEY uq_notification_recipient_booking_type (recipient_user_id, booking_id, type),
    INDEX idx_notifications_recipient_unread (recipient_user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opsional (Jika tabel bookings sudah ada di database lokal Anda):
-- ALTER TABLE bookings ADD COLUMN user_name VARCHAR(100) DEFAULT NULL AFTER user_id;
-- ALTER TABLE bookings ADD COLUMN user_dept VARCHAR(100) DEFAULT NULL AFTER user_name;
-- CREATE INDEX idx_bookings_room_date_status ON bookings (room_id, date, status);
-- CREATE INDEX idx_bookings_date_status ON bookings (date, status);
-- CREATE INDEX idx_bookings_user_status ON bookings (user_id, status);

-- Table: room_displays (Monitor Display Kiosk Pintu Ruangan)
CREATE TABLE IF NOT EXISTS room_displays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    display_token VARCHAR(64) NOT NULL UNIQUE,
    last_active TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data: users
-- Password default: password123 (bcrypt hash)
INSERT INTO users (id, name, email, password, role, avatar) VALUES
(1, 'Budi Santoso', 'budi@company.com', '$2y$10$4.a3y030p3yXyjpJS7Pd0RVvHwHe10xM0y3dE73v2bM8P5eR0kL6', 'user', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAOq_co_6Lvx0JMg_Ej6Bs8qdhO-YvU70HbBvNz48_8R6JhhvQQFxphs_NNte1gwVvkOHI8pSp0KqlejdX2v7TzZi6_ktGKRs1gVfl7xGmsw3o63JumKfiamt2RMFrdnVtmSMG4dndLkiIDeCCr88xzedn6Z4HA5M8hWUheTR5u8leccdRnwUdZJcDALajcjhals6o1D23riFUaYaA-QRrHefEusLlUDbg8lPLn4Dbfn490ZCsWlE_y'),
(2, 'Sarah Jenkins', 'sarah@company.com', '$2y$10$4.a3y030p3yXyjpJS7Pd0RVvHwHe10xM0y3dE73v2bM8P5eR0kL6', 'admin', 'https://lh3.googleusercontent.com/aida-public/AB6AXuA4DHMz3VXcoKd43Zm-E2rn_AZ1aAE9XG-w4tji_aeM7DNXXJt4mExYIq1w2lGF6m9a_SjO_p8sEizVnzpLSs6sSFFXbuyR7aCIk0EQ0-ZvV0ovpbnO3S5X8ynk-Eh_MIKzRSw3hGFD3MakmnWE6Dle-zsQ10OTJKoHcPyIqgCBz22PdYsCzJ7TjEZrNHyvtH36j35H5W_U6d9w0KnGNVaCdSdaP3ULPvHJtgi5nf2Pa3rM9LNio7Ko');

-- Seed Data: rooms (Daftar 6 Ruangan Rapat)
INSERT INTO rooms (id, code, name, capacity, location, floor, status, image, description, facilities) VALUES
(1, 'R-KBT', 'Ruang Rapat Kalibaru Timur', 15, 'Lantai 1, Divisi Komersial', 'Lantai 1', 'available', 'public/rooms/KalTim.jpeg', 'Ruang rapat representatif berkapasitas 15 kursi dengan suasana sejuk dan fasilitas proyektor untuk presentasi.', 'AC, Proyektor, 15 Kursi'),
(2, 'R-KBB', 'Ruang Rapat Kalibaru Barat', 15, 'Lantai 1, Divisi Keuangan', 'Lantai 1', 'occupied', 'public/rooms/KalBar.jpeg', 'Ruang rapat modern untuk koordinasi tim dan pertemuan kerja, dilengkapi AC dan proyektor resolusi tinggi.', 'AC, Proyektor, 15 Kursi'),
(3, 'R-SMD', 'Ruang Rapat Samudera', 60, 'Lantai 2', 'Lantai 2', 'available', 'public/rooms/Samudra.jpeg', 'Aula rapat besar berkapasitas 60 kursi yang didukung sound system mikrofon, pendingin AC, dan proyektor layar lebar.', 'AC, 60 Kursi, Proyektor, Mikrofon'),
(4, 'R-NST', 'Ruang Rapat Nusantara', 15, 'Lantai 2', 'Lantai 2', 'available', 'public/rooms/Nusantara.jpeg', 'Ruang pertemuan nyaman berkapasitas 15 kursi dengan TV LCD jernih untuk tayangan materi serta pendingin ruangan AC.', 'TV LCD, 15 Kursi, AC'),
(5, 'R-PLD', 'Ruang Rapat Pelabuhan Dalam', 10, 'Lantai 2, Divisi Teknik', 'Lantai 2', 'available', 'public/rooms/Peldam.jpeg', 'Ruang rapat fokus berkapasitas 10 kursi, cocok untuk evaluasi operasional harian dengan proyektor dan AC.', 'Proyektor, AC, 10 Kursi'),
(6, 'R-PLR', 'Ruang Rapat Pelra', 10, 'Lantai 2, Divisi SDM', 'Lantai 2', 'available', 'public/rooms/Pelra.jpeg', 'Ruang rapat tim berkapasitas 10 kursi dengan sirkulasi udara optimal berkat AC dan kipas, serta proyektor presentasi.', 'Proyektor, AC, Kipas, 10 Kursi');

-- Seed Data: room_displays (Display Token untuk Akses Monitor Kiosk)
INSERT INTO room_displays (id, room_id, display_name, display_token) VALUES
(1, 1, 'Display Kalibaru Timur', 'DISP-KBT-01'),
(2, 2, 'Display Kalibaru Barat', 'DISP-KBB-02'),
(3, 3, 'Display Samudera', 'DISP-SMD-03'),
(4, 4, 'Display Nusantara', 'DISP-NST-04'),
(5, 5, 'Display Pelabuhan Dalam', 'DISP-PLD-05'),
(6, 6, 'Display Pelra', 'DISP-PLR-06');

-- Seed Data: bookings
INSERT INTO bookings (id, user_id, room_id, title, date, start_time, end_time, purpose, attendees_count, status) VALUES
(1, 1, 1, 'Koordinasi Logistik & Pengiriman', CURDATE(), '09:00:00', '11:00:00', 'Review distribusi logistik mingguan dan sinkronisasi jadwal armada.', 12, 'confirmed'),
(2, 2, 2, 'Evaluasi Kinerja Bulanan', CURDATE(), '09:30:00', '11:30:00', 'Rapat evaluasi KPI dan peningkatan efisiensi kerja operasional.', 10, 'confirmed'),
(3, 1, 3, 'Rapat Akbar Sosialisasi Kebijakan Baru', CURDATE(), '13:30:00', '16:00:00', 'Pertemuan pleno seluruh divisi perusahaan bersama jajaran direksi.', 45, 'confirmed'),
(4, 2, 4, 'Briefing Presentasi LCD Display', CURDATE(), '14:00:00', '15:30:00', 'Uji materi visual dan review presentasi di layar TV LCD.', 8, 'confirmed');
