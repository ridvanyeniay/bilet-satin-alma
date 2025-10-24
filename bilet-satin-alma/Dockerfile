# Apache ve PHP 8.2 kurulu resmi imajı kullan
FROM php:8.2-apache

# Gerekli sistem kütüphanelerini VE sqlite3 CLI aracını yükle.
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libonig-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Gerekli PHP eklentilerini kur ve yapılandır.
# Tek bir RUN komutunda birleştirildi ve satır sonu boşlukları kontrol edildi.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite mbstring

# Apache'nin rewrite modülünü etkinleştir
RUN a2enmod rewrite

# Kendi özel Apache yapılandırma dosyamızı container'a kopyala
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Kendi özel PHP ayar dosyamızı kopyala
COPY docker/php/php.ini /usr/local/etc/php/conf.d/docker-php-charset.ini

# Otomatik kurulum script'ini container'a kopyala ve çalıştırılabilir yap
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

# WORKDIR, ENTRYPOINT'ten sonra gelir ve CMD için varsayılan dizini belirler
WORKDIR /var/www/html

# ENTRYPOINT'in sonunda çalıştırılacak olan varsayılan komut
CMD ["apache2-foreground"]
