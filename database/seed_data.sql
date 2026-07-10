-- ============================================================
--  YÖNETİM PANELİ - ÖRNEK/BAŞLANGIÇ VERİLERİ (seed_data.sql)
-- ============================================================
-- ÖNCE schema.sql çalıştırılmış olmalı.
--
-- Bu dosya çalıştıktan sonra panele şu bilgilerle giriş yapabilirsiniz:
--   Kullanıcı adı: admin   |  Şifre: 123456
-- (Şifreyi ilk girişten sonra "Şifremi Değiştir" sayfasından değiştirin.)
-- ============================================================

-- ============================================================
-- 1) MODÜLLER
-- ============================================================
INSERT INTO modules (module_key, module_name, icon, sort_order) VALUES
('stok_takip', 'Stok Takibi', 'box', 1),
('kullanici_yonetimi', 'Kullanıcı Yönetimi', 'users', 2),
('raporlar', 'Raporlar', 'chart-bar', 3),
('muhasebe', 'Muhasebe', 'wallet', 4),
('mail_yonetimi', 'Mail Yönetimi', 'mail', 5),
('ag_izleme', 'Ağ İzleme', 'network', 6);

-- Modül ID'lerini isimden kolayca bulmak için (INSERT sırasına göre 1-6 arası sabit kalır)
-- stok_takip=1, kullanici_yonetimi=2, raporlar=3, muhasebe=4, mail_yonetimi=5, ag_izleme=6

-- ============================================================
-- 2) SAYFALAR
-- ============================================================
INSERT INTO pages (module_id, page_key, page_title, file_path, sort_order) VALUES
-- Stok Takibi
(1, 'stok_listesi', 'Stok Listesi', 'modules/stok_takip/liste.php', 1),
(1, 'stok_ekle', 'Yeni Ürün Ekle', 'modules/stok_takip/ekle.php', 2),
(1, 'stok_kategori', 'Stok Kategorileri', 'modules/stok_takip/kategoriler.php', 3),
-- Kullanıcı Yönetimi
(2, 'kullanici_listesi', 'Kullanıcı Listesi', 'modules/kullanici_yonetimi/liste.php', 1),
(2, 'grup_listesi', 'Grup Listesi', 'modules/kullanici_yonetimi/gruplar.php', 2),
(2, 'yetki_yonetimi', 'Yetki Yönetimi', 'modules/kullanici_yonetimi/yetkiler.php', 3),
(2, 'sifre_degistir', 'Şifremi Değiştir', 'modules/kullanici_yonetimi/sifre_degistir.php', 4),
-- Raporlar
(3, 'satis_raporu', 'Satış Raporu', 'modules/raporlar/satis.php', 1),
(3, 'stok_raporu', 'Stok Raporu', 'modules/raporlar/stok.php', 2),
-- Muhasebe
(4, 'fatura_listesi', 'Fatura Listesi', 'modules/muhasebe/faturalar.php', 1),
(4, 'cari_hesaplar', 'Cari Hesaplar', 'modules/muhasebe/cariler.php', 2),
-- Mail Yönetimi
(5, 'mail_ayarlari', 'Mail Ayarları', 'modules/mail_yonetimi/ayarlar.php', 1),
(5, 'mail_kuyrugu', 'Mail Kuyruğu', 'modules/mail_yonetimi/kuyruk.php', 2),
(5, 'kur_botu_ayarlari', 'Kur Botu Ayarları', 'modules/mail_yonetimi/kur_botu.php', 3),
-- Ağ İzleme
(6, 'lisans_yonetimi', 'Lisans Yönetimi', 'modules/ag_izleme/lisanslar.php', 1),
(6, 'istemci_listesi', 'İstemci Listesi', 'modules/ag_izleme/istemciler.php', 2);

-- ============================================================
-- 3) MENÜLER (her sayfa için otomatik, page_title'ı menu_title olarak kullan)
-- ============================================================
INSERT INTO menus (module_id, page_id, menu_title, icon, sort_order)
SELECT module_id, id, page_title, 'circle', sort_order FROM pages;

-- ============================================================
-- 4) GRUPLAR
-- ============================================================
INSERT INTO groups_table (group_name, description) VALUES
('Yönetici', 'Tüm modüllere ve sayfalara tam erişim'),
('Depo Sorumlusu', 'Sadece stok modülüne erişim, sınırlı yetki'),
('Muhasebeci', 'Muhasebe ve raporlara erişim'),
('Görüntüleyici', 'Sadece görüntüleme yetkisi olan kullanıcılar');

-- ============================================================
-- 5) KULLANICILAR
-- ============================================================
-- Şifre (tüm örnek kullanıcılar için): 123456
-- Hash bcrypt ile üretildi, PHP password_verify() ile uyumludur.
INSERT INTO users (group_id, username, password_hash, full_name) VALUES
(1, 'admin', '$2b$12$Et2NN15.12BqWcVG1rGYCOjHdCGrvweG1c4y2uEXdxycWDtzRCJhO', 'Sistem Yöneticisi'),
(2, 'depocu', '$2b$12$Et2NN15.12BqWcVG1rGYCOjHdCGrvweG1c4y2uEXdxycWDtzRCJhO', 'Ahmet Yılmaz'),
(3, 'muhasebeci', '$2b$12$Et2NN15.12BqWcVG1rGYCOjHdCGrvweG1c4y2uEXdxycWDtzRCJhO', 'Ayşe Demir'),
(4, 'izleyici', '$2b$12$Et2NN15.12BqWcVG1rGYCOjHdCGrvweG1c4y2uEXdxycWDtzRCJhO', 'Mehmet Kaya');

-- ============================================================
-- 6) YETKİLER
-- ============================================================
-- Yönetici (id=1): her sayfada tam yetki
INSERT INTO group_page_permissions (group_id, page_id, can_view, can_add, can_update, can_delete)
SELECT 1, id, 1, 1, 1, 1 FROM pages;

-- Depo Sorumlusu (id=2): sadece stok sayfaları, sınırlı yetki
INSERT INTO group_page_permissions (group_id, page_id, can_view, can_add, can_update, can_delete) VALUES
(2, 1, 1, 0, 0, 0),
(2, 2, 1, 1, 0, 0),
(2, 3, 1, 0, 0, 0);

-- Muhasebeci (id=3): raporlar + muhasebe
INSERT INTO group_page_permissions (group_id, page_id, can_view, can_add, can_update, can_delete) VALUES
(3, 8, 1, 0, 0, 0),
(3, 9, 1, 0, 0, 0),
(3, 10, 1, 1, 1, 0),
(3, 11, 1, 1, 1, 0);

-- Görüntüleyici (id=4): sadece stok listesi ve satış raporu, salt görme
INSERT INTO group_page_permissions (group_id, page_id, can_view, can_add, can_update, can_delete) VALUES
(4, 1, 1, 0, 0, 0),
(4, 8, 1, 0, 0, 0);

-- ============================================================
-- 7) ÖRNEK STOK VERİLERİ (5 kategori x 5 ürün)
-- ============================================================
INSERT INTO stok_urunleri (urun_adi, kategori, stok_adedi) VALUES
('A4 Fotokopi Kağıdı (500 Adet)', 'Kırtasiye', 150),
('Tükenmez Kalem (Mavi)', 'Kırtasiye', 300),
('Zımba Makinesi', 'Kırtasiye', 25),
('Klasör (Kalın)', 'Kırtasiye', 80),
('Post-it Not Kağıdı', 'Kırtasiye', 120),
('USB Bellek 32GB', 'Elektronik', 45),
('Kablosuz Mouse', 'Elektronik', 30),
('HDMI Kablo 2m', 'Elektronik', 60),
('Klavye (Türkçe Q)', 'Elektronik', 20),
('Powerbank 10000mAh', 'Elektronik', 15),
('Toner Kartuşu HP 26A', 'Ofis Sarf Malzemesi', 8),
('Yazıcı Mürekkebi (Siyah)', 'Ofis Sarf Malzemesi', 22),
('Zarf (Beyaz, Büyük Boy)', 'Ofis Sarf Malzemesi', 200),
('Etiket Yazıcı Ruloları', 'Ofis Sarf Malzemesi', 40),
('Evrak Dosyası (Plastik)', 'Ofis Sarf Malzemesi', 95),
('Yüzey Temizleyici Sprey', 'Temizlik', 35),
('Kağıt Havlu (12li Paket)', 'Temizlik', 50),
('Çöp Poşeti (Büyük Boy)', 'Temizlik', 100),
('El Dezenfektanı 500ml', 'Temizlik', 60),
('Cam Temizleyici', 'Temizlik', 28),
('Filtre Kahve (1kg)', 'Gıda & İkram', 12),
('Çay (500gr Paket)', 'Gıda & İkram', 18),
('Şeker Küp Paketi', 'Gıda & İkram', 40),
('Bisküvi Çeşitleri', 'Gıda & İkram', 55),
('Su Pet Şişe (0.5L, 24lü Koli)', 'Gıda & İkram', 30);

-- ============================================================
-- 8) ÖRNEK MUHASEBE VERİLERİ
-- ============================================================
INSERT INTO faturalar (fatura_no, cari_adi, tutar, durum, fatura_tarihi) VALUES
('FAT-2026-001', 'ABC Ticaret Ltd.', 4500.00, 'Ödendi', '2026-06-15'),
('FAT-2026-002', 'Yıldız Market', 1250.50, 'Bekliyor', '2026-07-01');

INSERT INTO cari_hesaplar (cari_adi, telefon, bakiye) VALUES
('ABC Ticaret Ltd.', '0212 555 11 22', 0.00),
('Yıldız Market', '0532 444 33 22', 1250.50);

-- ============================================================
-- NOT: mail_settings, kur_botu_aliciler, api_lisanslar tabloları
-- kişiye/kuruma özel gizli bilgiler (SMTP şifresi, lisans kodları)
-- içerdiği için buraya ÖRNEK VERİ EKLENMEMİŞTİR.
-- Bu tablolar panel arayüzünden (Mail Ayarları, Kur Botu Ayarları,
-- Lisans Yönetimi sayfaları) doldurulmalıdır.
-- ============================================================
