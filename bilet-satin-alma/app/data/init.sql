-- Users Tablosu
-- Kullanıcıları (Yolcu, FirmaAdmin, Admin) saklar.
CREATE TABLE IF NOT EXISTS Users (
    id TEXT PRIMARY KEY,
    fullname TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'User',
    company_id TEXT, -- Sadece FirmaAdmin rolü için
    balance REAL NOT NULL DEFAULT 1000.0, -- Sanal bakiye
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Companies Tablosu
-- Otobüs firmalarını saklar.
CREATE TABLE IF NOT EXISTS Companies (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Trips Tablosu
-- Otobüs seferlerini saklar.
CREATE TABLE IF NOT EXISTS Trips (
    id TEXT PRIMARY KEY,
    company_id TEXT NOT NULL,
    departure_city TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    seat_count INTEGER NOT NULL,
    price REAL NOT NULL,
    FOREIGN KEY (company_id) REFERENCES Companies(id)
);

-- Bookings Tablosu
-- Satın alınmış biletleri (rezervasyonları) saklar.
CREATE TABLE IF NOT EXISTS Bookings (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    trip_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    price_paid REAL NOT NULL, -- Kupon indirimi sonrası ödenen nihai fiyat
    status TEXT NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (trip_id) REFERENCES Trips(id)
);

-- Coupons Tablosu
-- İndirim kuponlarını saklar.
CREATE TABLE IF NOT EXISTS Coupons (
    id TEXT PRIMARY KEY,
    code TEXT NOT NULL UNIQUE,
    discount_rate REAL NOT NULL, -- Örneğin: 0.20 (%20 indirim)
    usage_limit INTEGER NOT NULL,
    valid_until DATE NOT NULL,
    company_id TEXT -- NULL ise genel kupon, dolu ise firmaya özeldir.
);

-- Cities Tablosu
-- Otomatik tamamlama özelliği için 81 ilin listesini saklar.
CREATE TABLE IF NOT EXISTS Cities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    name_normalized TEXT NOT NULL UNIQUE -- Arama için standartlaştırılmış şehir adı
);