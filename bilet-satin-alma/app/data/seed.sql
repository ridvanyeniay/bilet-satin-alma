INSERT INTO Users (id, fullname, email, password, role) VALUES 
('admin-01', 'Süper Admin', 'admin@yeniay.com', '$2y$10$Biy/5bxmUwkATLnzcrKhxeqLFaGPx4mUZwSbJvr9hQNylt0YR4w5y', 'Admin');


INSERT INTO Companies (id, name) VALUES
('comp-01', 'Ay Turizm'), -- Örnek Firma 1
('comp-02', 'Vatan Seyahat'); -- Örnek Firma 2

--örnek
INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, seat_count, price) VALUES
('trip-101', 'comp-01', 'İstanbul', 'Ankara', '2025-11-15 09:00:00', '2025-11-15 15:30:00', 40, 580.00), -- Ay Turizm için
('trip-102', 'comp-02', 'İzmir', 'Antalya', '2025-11-20 11:00:00', '2025-11-20 18:00:00', 42, 650.00); -- Vatan Seyahat için


INSERT INTO Cities (name, name_normalized) VALUES
('Adana', 'adana'), ('Adıyaman', 'adiyaman'), ('Afyonkarahisar', 'afyonkarahisar'), ('Ağrı', 'agri'), ('Amasya', 'amasya'), ('Ankara', 'ankara'), ('Antalya', 'antalya'), ('Artvin', 'artvin'),
('Aydın', 'aydin'), ('Balıkesir', 'balikesir'), ('Bilecik', 'bilecik'), ('Bingöl', 'bingol'), ('Bitlis', 'bitlis'), ('Bolu', 'bolu'), ('Burdur', 'burdur'), ('Bursa', 'bursa'), ('Çanakkale', 'canakkale'),
('Çankırı', 'cankiri'), ('Çorum', 'corum'), ('Denizli', 'denizli'), ('Diyarbakır', 'diyarbakir'), ('Edirne', 'edirne'), ('Elazığ', 'elazig'), ('Erzincan', 'erzincan'), ('Erzurum', 'erzurum'), ('Eskişehir', 'eskisehir'),
('Gaziantep', 'gaziantep'), ('Giresun', 'giresun'), ('Gümüşhane', 'gumushane'), ('Hakkâri', 'hakkari'), ('Hatay', 'hatay'), ('Isparta', 'isparta'), ('Mersin', 'mersin'), ('İstanbul', 'istanbul'), ('İzmir', 'izmir'),
('Kars', 'kars'), ('Kastamonu', 'kastamonu'), ('Kayseri', 'kayseri'), ('Kırklareli', 'kirklareli'), ('Kırşehir', 'kirsehir'), ('Kocaeli', 'kocaeli'), ('Konya', 'konya'), ('Kütahya', 'kutahya'), ('Malatya', 'malatya'),
('Manisa', 'manisa'), ('Kahramanmaraş', 'kahramanmaras'), ('Mardin', 'mardin'), ('Muğla', 'mugla'), ('Muş', 'mus'), ('Nevşehir', 'nevsehir'), ('Niğde', 'nigde'), ('Ordu', 'ordu'), ('Rize', 'rize'), ('Sakarya', 'sakarya'),
('Samsun', 'samsun'), ('Siirt', 'siirt'), ('Sinop', 'sinop'), ('Sivas', 'sivas'), ('Tekirdağ', 'tekirdag'), ('Tokat', 'tokat'), ('Trabzon', 'trabzon'), ('Tunceli', 'tunceli'), ('Şanlıurfa', 'sanliurfa'), ('Uşak', 'usak'),
('Van', 'van'), ('Yozgat', 'yozgat'), ('Zonguldak', 'zonguldak'), ('Aksaray', 'aksaray'), ('Bayburt', 'bayburt'), ('Karaman', 'karaman'), ('Kırıkkale', 'kirikkale'), ('Batman', 'batman'), ('Şırnak', 'sirnak'),
('Bartın', 'bartin'), ('Ardahan', 'ardahan'), ('Iğdır', 'igdir'), ('Yalova', 'yalova'), ('Karabük', 'karabuk'), ('Kilis', 'kilis'), ('Osmaniye', 'osmaniye'), ('Düzce', 'duzce');