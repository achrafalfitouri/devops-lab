# ================================
# Composer build stage
# ================================
FROM composer:2 AS composer-build

WORKDIR /app

# 👇 Explicit backend path
COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ================================
# Runtime stage
# ================================
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www

# 👇 Copy backend source
COPY backend/ .

# 👇 Vendor from composer stage
COPY --from=composer-build /app/vendor ./vendor

# 👇 PHP config paths from root
COPY docker/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# 👇 Supervisor path from root
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chown -R www-data:www-data /var/www \
 && chmod -R 755 storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:clear && php artisan cache:clear || true; php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]



