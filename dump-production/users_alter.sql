-- =====================================================================
-- users_alter.sql
-- Menyelaraskan skema tabel `users` (database utama dpmptsp_new / data_local)
-- dengan kebutuhan aplikasi. Aman dijalankan berulang (idempotent).
--
-- Kolom yang ditambahkan:
--   is_admin_arsip   : flag admin modul Arsip Digital  (setelah is_admin_kepegawaian)
--   is_admin_bangkit : flag admin modul Barang Kita    (setelah is_admin_arsip)
-- =====================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `_add_column_if_missing` $$
CREATE PROCEDURE `_add_column_if_missing`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_ddl TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

CALL `_add_column_if_missing`('users', 'is_admin_arsip',
    '`is_admin_arsip` TINYINT NOT NULL DEFAULT 0 AFTER `is_admin_kepegawaian`');

CALL `_add_column_if_missing`('users', 'is_admin_bangkit',
    '`is_admin_bangkit` TINYINT NOT NULL DEFAULT 0 AFTER `is_admin_arsip`');

DROP PROCEDURE IF EXISTS `_add_column_if_missing`;

-- Cek hasil
-- SELECT id, nama, email, role, is_admin_kepegawaian, is_admin_arsip, is_admin_bangkit FROM users LIMIT 5;
