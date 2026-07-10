-- ============================================================
--  YÖNETİM PANELİ - VERİTABANI ŞEMASI (schema.sql)
-- ============================================================
-- Bu dosya, panel_db veritabanının TÜM tablolarını sıfırdan oluşturur.
-- Kurulum sırası: önce bu dosyayı, sonra seed_data.sql'i çalıştırın.
--
-- Kullanım (phpMyAdmin):
--   1) "panel_db" adında bir veritabanı oluşturun (utf8mb4_turkish_ci)
--   2) Bu dosyanın tamamını SQL sekmesinde çalıştırın
--   3) Ardından seed_data.sql dosyasını çalıştırın
-- ============================================================

-- ============ MODÜLLER ============
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(50) NOT NULL UNIQUE,
    module_name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ SAYFALAR ============
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    page_key VARCHAR(100) NOT NULL UNIQUE,
    page_title VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ MENÜLER ============
CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    page_id INT DEFAULT NULL,
    menu_title VARCHAR(150) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ GRUPLAR ============
CREATE TABLE groups_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ KULLANICILAR (grup zorunlu) ============
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups_table(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ GRUP-SAYFA YETKİLERİ ============
CREATE TABLE group_page_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    page_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_add TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_group_page (group_id, page_id),
    FOREIGN KEY (group_id) REFERENCES groups_table(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ STOK TAKİBİ MODÜLÜ ============
CREATE TABLE stok_urunleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_adi VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) DEFAULT NULL,
    stok_adedi INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE satislar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_id INT NOT NULL,
    adet INT NOT NULL,
    birim_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    satis_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (urun_id) REFERENCES stok_urunleri(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ MUHASEBE MODÜLÜ ============
CREATE TABLE faturalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fatura_no VARCHAR(50) NOT NULL UNIQUE,
    cari_adi VARCHAR(150) NOT NULL,
    tutar DECIMAL(10,2) NOT NULL DEFAULT 0,
    durum ENUM('Bekliyor','Ödendi','İptal') NOT NULL DEFAULT 'Bekliyor',
    fatura_tarihi DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE cari_hesaplar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cari_adi VARCHAR(150) NOT NULL,
    telefon VARCHAR(30) DEFAULT NULL,
    bakiye DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ MAIL YÖNETİMİ MODÜLÜ ============
CREATE TABLE mail_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(150) NOT NULL,
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_username VARCHAR(150) NOT NULL,
    smtp_password VARCHAR(255) NOT NULL,
    smtp_encryption ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
    gonderen_eposta VARCHAR(150) NOT NULL,
    gonderen_adi VARCHAR(150) NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE mail_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alici_eposta VARCHAR(150) NOT NULL,
    alici_adi VARCHAR(150) DEFAULT NULL,
    konu VARCHAR(255) NOT NULL,
    icerik LONGTEXT NOT NULL,
    durum ENUM('Bekliyor','Gonderiliyor','Gonderildi','Hata') NOT NULL DEFAULT 'Bekliyor',
    hata_mesaji TEXT DEFAULT NULL,
    deneme_sayisi INT NOT NULL DEFAULT 0,
    kaynak VARCHAR(100) DEFAULT NULL,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    gonderim_tarihi DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE kur_botu_aliciler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eposta VARCHAR(150) NOT NULL,
    ad_soyad VARCHAR(150) DEFAULT NULL,
    aktif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ============ AĞ İZLEME MODÜLÜ ============
CREATE TABLE api_lisanslar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lisans_kodu VARCHAR(64) NOT NULL UNIQUE,
    etiket VARCHAR(150) DEFAULT NULL,
    aktif TINYINT(1) DEFAULT 1,
    son_kullanim DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE istemci_bilgileri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lisans_id INT NOT NULL UNIQUE,
    bilgisayar_adi VARCHAR(150) DEFAULT NULL,
    kullanici_adi VARCHAR(150) DEFAULT NULL,
    mac_adresi VARCHAR(50) DEFAULT NULL,
    ip_adresi VARCHAR(50) DEFAULT NULL,
    subnet_mask VARCHAR(50) DEFAULT NULL,
    default_gateway VARCHAR(50) DEFAULT NULL,
    son_guncelleme DATETIME DEFAULT NULL,
    FOREIGN KEY (lisans_id) REFERENCES api_lisanslar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE istemci_bilgileri_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lisans_id INT NOT NULL,
    bilgisayar_adi VARCHAR(150) DEFAULT NULL,
    kullanici_adi VARCHAR(150) DEFAULT NULL,
    mac_adresi VARCHAR(50) DEFAULT NULL,
    ip_adresi VARCHAR(50) DEFAULT NULL,
    subnet_mask VARCHAR(50) DEFAULT NULL,
    default_gateway VARCHAR(50) DEFAULT NULL,
    kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lisans_id) REFERENCES api_lisanslar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
