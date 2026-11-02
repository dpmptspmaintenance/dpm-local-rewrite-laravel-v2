-- Dijalankan otomatis sekali saat volume dbdata masih kosong.
-- Membuat dua database yang dipakai aplikasi (lihat docker/mandiri.env).
CREATE DATABASE IF NOT EXISTS `dpmptsp_new` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `kepegawaian` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
