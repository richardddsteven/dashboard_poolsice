FROM php:8.3-apache

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    git zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev \
    libcurl4-openssl-dev libpq-dev ca-certificates && \
    docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd intl && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Ensure storage and cache dirs exist and are writable
RUN mkdir -p storage/app/public && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Enable Apache rewrite
RUN a2enmod rewrite

# Serve from public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
