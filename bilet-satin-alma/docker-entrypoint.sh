#!/bin/sh

# Veritabanı dosyasının yolu
DB_FILE="/var/www/html/data/database.db"

# Eğer veritabanı dosyası MEVCUT DEĞİLSE, ilk kurulumu yap
if [ ! -f "$DB_FILE" ]; then
    echo "Veritabanı bulunamadı. İlk kurulum yapılıyor..."

    # 1. Şemayı (tabloları) oluştur
    sqlite3 "$DB_FILE" < /var/www/html/data/init.sql

    # 2. Tohumlama: Gerekli tüm başlangıç verilerini ekle
    sqlite3 "$DB_FILE" < /var/www/html/data/seed.sql

    # 3. İzinleri ayarla
    chown -R www-data:www-data /var/www/html/data

    echo "Kurulum tamamlandı."
fi

# Orijinal Docker komutunu çalıştır (Apache'yi başlat)
exec "$@"
