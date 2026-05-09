-- ============================================================
-- PROJE: KURUMSAL PERSONEL VE PROJE YÖNETİM SİSTEMİ
-- MySQL Versiyonu — Genişletilmiş Veri Seti
-- ============================================================

CREATE DATABASE IF NOT EXISTS vanguard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE vanguard_db;

-- ============================================================
-- ESKİ TABLOLARI TEMİZLE (Hata almamak için)
-- ============================================================
DROP TABLE IF EXISTS salary_;
DROP TABLE IF EXISTS project_;
DROP TABLE IF EXISTS customer_;
DROP TABLE IF EXISTS supplier_;
DROP TABLE IF EXISTS personnel_;
DROP TABLE IF EXISTS other_;
DROP TABLE IF EXISTS technical_staff_;
DROP TABLE IF EXISTS manager_;
DROP TABLE IF EXISTS departments_;
DROP TABLE IF EXISTS company_;

-- ============================================================
-- TABLO TANIMLARI (DDL)
-- ============================================================


CREATE TABLE company_ (
    id          INT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,
    phn_nmbr    CHAR(11) NOT NULL,
    fax_nmbr    CHAR(10) NOT NULL UNIQUE
);


CREATE TABLE departments_ (
    id          INT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,
    phn_ext     CHAR(3),
    cmpny_id    INT NOT NULL,
    CONSTRAINT fk_dept_comp_ FOREIGN KEY (cmpny_id) REFERENCES company_(id)
);


CREATE TABLE manager_ (
    id          INT PRIMARY KEY,
    mngmnt_lvl  VARCHAR(50) NOT NULL
);

CREATE TABLE technical_staff_ (
    id          INT PRIMARY KEY,
    tech_skill  VARCHAR(100) NOT NULL
);

CREATE TABLE other_ (
    id          INT PRIMARY KEY,
    note        VARCHAR(100)
);


CREATE TABLE personnel_ (
    id          INT PRIMARY KEY,
    f_name      VARCHAR(50) NOT NULL,
    l_name      VARCHAR(50) NOT NULL,
    hire_date   DATE NOT NULL DEFAULT (CURRENT_DATE),
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    mnthly_hrs  SMALLINT NOT NULL DEFAULT 0,
    dept_id     INT NOT NULL,
    mgr_id      INT,
    mngr_id     INT,
    staff_id    INT,
    other_id    INT,
    CONSTRAINT fk_per_dept_  FOREIGN KEY (dept_id)    REFERENCES departments_(id),
    CONSTRAINT fk_per_self_  FOREIGN KEY (mgr_id)     REFERENCES personnel_(id),
    CONSTRAINT fk_per_mng_   FOREIGN KEY (mngr_id)    REFERENCES manager_(id),
    CONSTRAINT fk_per_tch_   FOREIGN KEY (staff_id)   REFERENCES technical_staff_(id),
    CONSTRAINT fk_per_oth_   FOREIGN KEY (other_id)   REFERENCES other_(id)
);


CREATE TABLE supplier_ (
    id          INT PRIMARY KEY,
    first_name  VARCHAR(50) NOT NULL,
    last_name   VARCHAR(50) NOT NULL,
    address     VARCHAR(100),
    email       VARCHAR(100) NOT NULL UNIQUE,
    phn_nmbr    CHAR(11) NOT NULL,
    cmpny_id    INT NOT NULL,
    CONSTRAINT fk_sup_comp_ FOREIGN KEY (cmpny_id) REFERENCES company_(id)
);


CREATE TABLE customer_ (
    id          INT PRIMARY KEY,
    first_name  VARCHAR(50) NOT NULL,
    last_name   VARCHAR(50) NOT NULL,
    phn_nmbr    CHAR(11) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    splr_id     INT NOT NULL,
    CONSTRAINT fk_cst_sup_ FOREIGN KEY (splr_id) REFERENCES supplier_(id)
);


CREATE TABLE project_ (
    id          INT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,
    start_date  DATE NOT NULL,
    budget      DECIMAL(12,2) NOT NULL,
    end_date    DATE,
    sale_price  DECIMAL(12,2) NOT NULL,
    description VARCHAR(100),
    customer_id INT,
    prsnl_id    INT NOT NULL,
    dept_id     INT,
    CONSTRAINT fk_prj_cust_ FOREIGN KEY (customer_id) REFERENCES customer_(id),
    CONSTRAINT fk_prj_psnl_ FOREIGN KEY (prsnl_id)    REFERENCES personnel_(id),
    CONSTRAINT fk_prj_dept_ FOREIGN KEY (dept_id)    REFERENCES departments_(id),
    CONSTRAINT prj_arc_chk_ CHECK (
        (dept_id IS NOT NULL AND customer_id IS NULL) OR
        (dept_id IS NULL     AND customer_id IS NOT NULL)
    )
);


CREATE TABLE salary_ (
    id          INT PRIMARY KEY,
    amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    pymnt_date  DATE NOT NULL DEFAULT (CURRENT_DATE),
    psnl_id     INT NOT NULL,
    CONSTRAINT fk_sal_per_ FOREIGN KEY (psnl_id) REFERENCES personnel_(id)
);

-- ============================================================
-- VERİ GİRİŞİ (INSERT)
-- ============================================================

-- 1. ŞİRKET
INSERT INTO company_ VALUES (1, 'Vanguard Logic', '05411234567', 'TR19820510');

-- 2. DEPARTMANLAR — 7 adet
INSERT INTO departments_ VALUES (10, 'Cyber Security',    '101', 1);
INSERT INTO departments_ VALUES (20, 'Embedded Systems',  '102', 1);
INSERT INTO departments_ VALUES (30, 'Software Dev',      '103', 1);
INSERT INTO departments_ VALUES (40, 'R&D',               '104', 1);
INSERT INTO departments_ VALUES (50, 'Human Resources',   '105', 1);
INSERT INTO departments_ VALUES (60, 'Cloud & DevOps',    '106', 1);
INSERT INTO departments_ VALUES (70, 'AI & Data Science', '107', 1);

-- 3. YÖNETİCİ TİPLERİ
INSERT INTO manager_ VALUES (101, 'CEO');
INSERT INTO manager_ VALUES (102, 'CTO');
INSERT INTO manager_ VALUES (103, 'CFO');
INSERT INTO manager_ VALUES (104, 'COO');
INSERT INTO manager_ VALUES (105, 'Founding Partner');
INSERT INTO manager_ VALUES (106, 'Department Head');
INSERT INTO manager_ VALUES (107, 'Team Lead');

-- 4. TEKNİK PERSONEL TİPLERİ
INSERT INTO technical_staff_ VALUES (201, 'Cybersecurity Analyst');
INSERT INTO technical_staff_ VALUES (202, 'Network Engineer');
INSERT INTO technical_staff_ VALUES (203, 'Full Stack Developer');
INSERT INTO technical_staff_ VALUES (204, 'DevOps Engineer');
INSERT INTO technical_staff_ VALUES (205, 'Hardware & Kernel Design');
INSERT INTO technical_staff_ VALUES (206, 'Machine Learning Engineer');
INSERT INTO technical_staff_ VALUES (207, 'Penetration Tester');
INSERT INTO technical_staff_ VALUES (208, 'Embedded Software Engineer');
INSERT INTO technical_staff_ VALUES (209, 'Data Scientist');
INSERT INTO technical_staff_ VALUES (210, 'Cloud Architect');

-- 5. DİĞER PERSONEL TİPLERİ
INSERT INTO other_ VALUES (301, 'Administrative Assistant');
INSERT INTO other_ VALUES (302, 'Senior HR Specialist');
INSERT INTO other_ VALUES (303, 'Legal Advisor');
INSERT INTO other_ VALUES (304, 'Finance Specialist');
INSERT INTO other_ VALUES (305, 'Technical Writer');
INSERT INTO other_ VALUES (306, 'Stajyer');

-- 6. PERSONEL — 15 kişi + 1 admin = 16 kayıt
--    Şifre: SHA2(şifre, 256)  |  Genel: pass1234  |  Admin: admin123
INSERT INTO personnel_ VALUES (1,  'Admin',   'User',     '2020-01-01', 'admin@vanguard.com',          SHA2('admin123', 256), 160, 10, NULL, 101, NULL, NULL);

-- Yöneticiler (5 kişi)
INSERT INTO personnel_ VALUES (10, 'Kemal',   'Yılmaz',   '2018-03-10', 'kemal.yilmaz@vanguard.com',   SHA2('pass1234', 256), 160, 10, NULL, 101, NULL, NULL);
INSERT INTO personnel_ VALUES (11, 'Selin',   'Arslan',   '2018-07-01', 'selin.arslan@vanguard.com',    SHA2('pass1234', 256), 160, 20, NULL, 102, NULL, NULL);
INSERT INTO personnel_ VALUES (12, 'Orhan',   'Koç',      '2017-05-20', 'orhan.koc@vanguard.com',       SHA2('pass1234', 256), 160, 30, NULL, 103, NULL, NULL);
INSERT INTO personnel_ VALUES (13, 'Nilüfer', 'Şahin',    '2019-09-15', 'nilufer.sahin@vanguard.com',   SHA2('pass1234', 256), 160, 50, NULL, 104, NULL, NULL);
INSERT INTO personnel_ VALUES (14, 'Ahmet',   'Öz',       '1982-03-15', 'ahmet.oz@vanguard.com',        SHA2('alpha82',  256), 160, 20, NULL, 105, NULL, NULL);

-- Teknik Personel (6 kişi)
INSERT INTO personnel_ VALUES (30, 'Mehmet',  'Çelik',    '2020-01-15', 'mehmet.celik@vanguard.com',    SHA2('pass1234', 256), 160, 10, 10,  NULL, 201, NULL);
INSERT INTO personnel_ VALUES (40, 'Can',     'Özdemir',  '2020-08-03', 'can.ozdemir@vanguard.com',     SHA2('pass1234', 256), 160, 20, 11,  NULL, 208, NULL);
INSERT INTO personnel_ VALUES (50, 'Zeynep',  'Kurt',     '2021-02-10', 'zeynep.kurt@vanguard.com',     SHA2('pass1234', 256), 160, 30, 12,  NULL, 203, NULL);
INSERT INTO personnel_ VALUES (60, 'Deniz',   'Kaya',     '2019-05-20', 'deniz.kaya@vanguard.com',      SHA2('pass1234', 256), 160, 40, NULL, NULL, 209, NULL);
INSERT INTO personnel_ VALUES (80, 'Alper',   'Sönmez',   '2021-04-01', 'alper.sonmez@vanguard.com',    SHA2('pass1234', 256), 160, 60, NULL, NULL, 204, NULL);
INSERT INTO personnel_ VALUES (90, 'Elif',    'Çakır',    '2021-10-01', 'elif.cakir@vanguard.com',      SHA2('pass1234', 256), 160, 70, NULL, NULL, 206, NULL);

-- Diğer Personel (4 kişi)
INSERT INTO personnel_ VALUES (70, 'Gül',     'Arslan',   '2016-09-01', 'gul.arslan@vanguard.com',      SHA2('pass1234', 256), 160, 50, NULL, NULL, NULL, 302);
INSERT INTO personnel_ VALUES (95, 'Oya',     'Başaran',  '2018-11-01', 'oya.basaran@vanguard.com',     SHA2('pass1234', 256), 160, 70, NULL, NULL, NULL, 304);

-- Stajyerler (2 kişi)
INSERT INTO personnel_ VALUES (31, 'Ayşe',    'Demir',    '2021-09-01', 'ayse.demir@vanguard.com',      SHA2('pass1234', 256), 140, 40, 10,  NULL, NULL, 306);
INSERT INTO personnel_ VALUES (71, 'Canan',   'Demir',    '2020-03-15', 'canan.demir@vanguard.com',     SHA2('pass1234', 256), 160, 60, 70,  NULL, NULL, 306);

-- 7. TEDARİKÇİLER — 7 adet
INSERT INTO supplier_ VALUES (500, 'Nano',    'Chipset A.S.',     'Ankara Teknopark Blok-A',    'sales@nanochip.com',       '03125554433', 1);
INSERT INTO supplier_ VALUES (501, 'Delta',   'Electronics Ltd.', 'İstanbul TGB Kat-3',         'info@deltaelec.com',        '02125554433', 1);
INSERT INTO supplier_ VALUES (502, 'Sigma',   'Defense Tech',     'Ankara OSB 4.Cad No:12',     'contact@sigmadefense.com',  '03124441122', 1);
INSERT INTO supplier_ VALUES (503, 'Apex',    'Cloud Systems',    'İzmir Teknopark B2-401',     'sales@apexcloud.io',        '02325553344', 1);
INSERT INTO supplier_ VALUES (504, 'Helix',   'AI Solutions',     'Ankara Bilkent TTO 204',     'info@helixai.com.tr',       '03123332211', 1);
INSERT INTO supplier_ VALUES (505, 'Orion',   'Embedded Labs',    'Bursa Teknopark K1-15',      'support@orionlabs.com.tr',  '02245556677', 1);
INSERT INTO supplier_ VALUES (506, 'Vertex',  'Cyber Defense',    'Kocaeli Teknopark A3-210',   'sales@vertexcyber.com.tr',  '02625557788', 1);

-- 8. MÜŞTERİLER — 7 adet
-- Doğrudan müşteriler
INSERT INTO customer_ VALUES (1, 'Savunma',  'Sanayii Bşk.',  '03121234567', 'ihale@ssb.gov.tr',           500);
INSERT INTO customer_ VALUES (2, 'TÜBİTAK', 'BİLGEM',        '03121239999', 'proje@tubitak.gov.tr',        501);
INSERT INTO customer_ VALUES (3, 'Türk',     'Telekom A.Ş.',  '03124567890', 'yazilim@turktelekom.com.tr',  504);
INSERT INTO customer_ VALUES (4, 'Sağlık',   'Bakanlığı',     '03123456789', 'bt@saglik.gov.tr',            506);
-- Tedarikçi aracılığıyla gelen müşteriler
INSERT INTO customer_ VALUES (5, 'NATO',     'NCIA',          '03129998877', 'procurement@ncia.nato.int',   502);
INSERT INTO customer_ VALUES (6, 'Bosch',    'Türkiye',       '02162223344', 'tech@bosch.com.tr',           505);
INSERT INTO customer_ VALUES (7, 'Turkcell', 'Teknoloji',     '02163334455', 'partner@turkcell.com.tr',     503);

-- 9. PROJELER — 11 adet  (ARC kısıtı: dept_id XOR customer_id)

-- Müşteri projeleri (11 adet) (customer_id, prsnl_id, dept_id)
INSERT INTO project_ VALUES (100, 'Aegis Defense',      '2025-09-01', 7500000.00, '2026-12-31', 18000000.00, 'Otonom sinir guvenligi sistemi.',       1, 10, NULL);
INSERT INTO project_ VALUES (101, 'SmartSensor v3',     '2026-01-15', 1200000.00, '2026-10-30',  3200000.00, 'IoT sensor agi guncellemesi.',          6, 11, NULL);
INSERT INTO project_ VALUES (102, 'Quantum Shield',     '2026-02-01', 4500000.00, '2027-06-30', 11000000.00, 'Kuantum sifreleme alt yapisi.',         2, 12, NULL);
INSERT INTO project_ VALUES (103, 'TeleOps Platform',   '2025-11-10', 2800000.00, '2026-08-31',  6500000.00, 'Uzak operasyon yonetim platformu.',     3, 60, NULL);
INSERT INTO project_ VALUES (104, 'HealthNet AI',       '2026-03-01', 3100000.00, '2027-03-01',  7800000.00, 'Saglik veri analitik sistemi.',         4, 13, NULL);
INSERT INTO project_ VALUES (105, 'NATO C2 Gateway',    '2025-06-01', 9200000.00, '2026-05-31', 22500000.00, 'NATO komuta kontrol entegrasyonu.',     5, 80, NULL);
INSERT INTO project_ VALUES (106, 'Edge AI Module',     '2026-04-01', 1800000.00,  NULL,          4500000.00, 'Uc nokta yapay zeka modulu.',           7, 90, NULL);
INSERT INTO project_ VALUES (111, 'Aegis Shield v2',    '2026-05-01', 3500000.00,  NULL,         8000000.00, 'Savunma altyapisi genisletme.',         1, 11, NULL);
INSERT INTO project_ VALUES (112, 'Aegis Sentinel',     '2026-08-15', 5200000.00, '2028-01-01', 12500000.00, 'Ileri duzey takip sistemi.',            1, 12, NULL);
INSERT INTO project_ VALUES (113, 'BİLGEM DataLake',    '2026-06-01', 2100000.00, '2027-06-01',  5500000.00, 'Veri golu ve analitik projesi.',        2, 13, NULL);
INSERT INTO project_ VALUES (114, 'Bosch IoT Core',     '2026-07-01', 4800000.00,  NULL,        10000000.00, 'Endustriyel IoT platform guncellemesi.',6, 30, NULL);

-- Dahili projeler (4 adet) (customer_id, prsnl_id, dept_id)
INSERT INTO project_ VALUES (107, 'InternalSecOps',     '2025-12-01',  450000.00, '2026-06-30',       0.00, 'Ic guvenlik operasyonlari.',             NULL, 30, 10);
INSERT INTO project_ VALUES (108, 'DevPlatform v2',     '2026-01-01',  320000.00,  NULL,               0.00, 'Ic gelistirme platformu yukseltme.',     NULL, 40, 30);
INSERT INTO project_ VALUES (109, 'CloudMigration',     '2026-02-15',  680000.00, '2026-11-30',       0.00, 'Sunucu altyapisi buluta tasinmasi.',     NULL, 50, 60);
INSERT INTO project_ VALUES (110, 'AI Research Lab',    '2025-10-01',  900000.00,  NULL,               0.00, 'Makine ogrenimi ar-ge calismalari.',     NULL, 70, 70);

-- 10. MAAŞLAR — Nisan + Mayıs 2026 (ikramiye yok)
--     15 personel × 2 ay = 30 kayıt

-- Mayıs 2026
INSERT INTO salary_ VALUES (5001, 285000.00, '2026-05-15', 10);
INSERT INTO salary_ VALUES (5002, 265000.00, '2026-05-15', 11);
INSERT INTO salary_ VALUES (5003, 245000.00, '2026-05-15', 12);
INSERT INTO salary_ VALUES (5004, 240000.00, '2026-05-15', 13);
INSERT INTO salary_ VALUES (5005, 310000.00, '2026-05-15', 14);
INSERT INTO salary_ VALUES (5006, 160000.00, '2026-05-15', 30);
INSERT INTO salary_ VALUES (5007, 148000.00, '2026-05-15', 31);
INSERT INTO salary_ VALUES (5008, 155000.00, '2026-05-15', 40);
INSERT INTO salary_ VALUES (5009, 152000.00, '2026-05-15', 50);
INSERT INTO salary_ VALUES (5010, 175000.00, '2026-05-15', 60);
INSERT INTO salary_ VALUES (5011, 162000.00, '2026-05-15', 80);
INSERT INTO salary_ VALUES (5012, 168000.00, '2026-05-15', 90);
INSERT INTO salary_ VALUES (5013, 105000.00, '2026-05-15', 70);
INSERT INTO salary_ VALUES (5014,  92000.00, '2026-05-15', 71);
INSERT INTO salary_ VALUES (5015, 110000.00, '2026-05-15', 95);

-- Nisan 2026
INSERT INTO salary_ VALUES (5101, 285000.00, '2026-04-15', 10);
INSERT INTO salary_ VALUES (5102, 265000.00, '2026-04-15', 11);
INSERT INTO salary_ VALUES (5103, 245000.00, '2026-04-15', 12);
INSERT INTO salary_ VALUES (5104, 240000.00, '2026-04-15', 13);
INSERT INTO salary_ VALUES (5105, 310000.00, '2026-04-15', 14);
INSERT INTO salary_ VALUES (5106, 160000.00, '2026-04-15', 30);
INSERT INTO salary_ VALUES (5107, 148000.00, '2026-04-15', 31);
INSERT INTO salary_ VALUES (5108, 155000.00, '2026-04-15', 40);
INSERT INTO salary_ VALUES (5109, 152000.00, '2026-04-15', 50);
INSERT INTO salary_ VALUES (5110, 175000.00, '2026-04-15', 60);
INSERT INTO salary_ VALUES (5111, 162000.00, '2026-04-15', 80);
INSERT INTO salary_ VALUES (5112, 168000.00, '2026-04-15', 90);
INSERT INTO salary_ VALUES (5113, 105000.00, '2026-04-15', 70);
INSERT INTO salary_ VALUES (5114,  92000.00, '2026-04-15', 71);
INSERT INTO salary_ VALUES (5115, 110000.00, '2026-04-15', 95);

-- ============================================================
-- DML SORGULARI (Rapor için 5 zorunlu sorgu)
-- ============================================================

-- 1) ALT SORGU: Ortalama maaşın üzerinde maaş alan personeller
SELECT p.f_name, p.l_name, s.amount,
       ROUND(s.amount - (SELECT AVG(amount) FROM salary_), 2) AS ortalama_farki
FROM personnel_ p
JOIN salary_ s ON s.psnl_id = p.id
WHERE s.amount > (SELECT AVG(amount) FROM salary_)
ORDER BY s.amount DESC;

-- 2) JOIN: Personel, departman, şirket ve personel tipi
SELECT p.f_name, p.l_name,
       d.name AS department,
       c.name AS company,
       CASE
           WHEN p.mngr_id  IS NOT NULL THEN CONCAT('Yönetici — ', m.mngmnt_lvl)
           WHEN p.staff_id IS NOT NULL THEN CONCAT('Teknik — ',   ts.tech_skill)
           WHEN p.other_id IS NOT NULL THEN CONCAT('Diğer — ',    o.note)
           ELSE 'Belirsiz'
       END AS personel_tipi
FROM personnel_ p
JOIN departments_      d  ON p.dept_id  = d.id
JOIN company_          c  ON d.cmpny_id = c.id
LEFT JOIN manager_         m  ON p.mngr_id  = m.id
LEFT JOIN technical_staff_ ts ON p.staff_id = ts.id
LEFT JOIN other_           o  ON p.other_id = o.id
ORDER BY d.name, p.f_name;

-- 3) GROUP BY: Departman başına personel sayısı, toplam ve ortalama maaş
SELECT d.name AS department,
       COUNT(DISTINCT p.id)    AS personel_sayisi,
       SUM(s.amount)           AS toplam_maas,
       ROUND(AVG(s.amount), 2) AS ortalama_maas,
       MAX(s.amount)           AS en_yuksek_maas
FROM departments_ d
JOIN personnel_ p ON p.dept_id = d.id
JOIN salary_    s ON s.psnl_id = p.id
GROUP BY d.id, d.name
ORDER BY toplam_maas DESC;

-- 4) TARİH FONKSİYONU: İşe başlama tarihinden bu yana geçen süre
SELECT p.f_name, p.l_name,
       p.hire_date,
       TIMESTAMPDIFF(YEAR,  p.hire_date, CURDATE()) AS kidem_yil,
       TIMESTAMPDIFF(MONTH, p.hire_date, CURDATE()) AS toplam_ay,
       DATE_FORMAT(p.hire_date, '%d %M %Y')          AS formatted_tarih
FROM personnel_ p
ORDER BY p.hire_date;

-- 5) KARAKTER FONKSİYONU: İsim büyük harf, email domain, uzunluk
SELECT UPPER(p.f_name)                   AS isim_buyuk,
       UPPER(p.l_name)                   AS soyisim_buyuk,
       p.email,
       SUBSTRING_INDEX(p.email, '@', -1) AS email_domain,
       LENGTH(p.email)                   AS email_uzunluk,
       CONCAT(p.f_name, ' ', p.l_name)   AS tam_ad
FROM personnel_ p
ORDER BY p.f_name;
