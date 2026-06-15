FROM node:22-bookworm-slim AS frontend-builder

WORKDIR /app

COPY frontend/package*.json ./frontend/
RUN cd frontend && npm ci

COPY backend ./backend
COPY frontend ./frontend
RUN cd frontend && npm run build


FROM php:8.4-apache-bookworm AS app

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        posix \
        xml \
        zip \
    && a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel-public.conf \
    && a2enconf laravel-public \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY backend ./
COPY --from=frontend-builder /app/backend/public/build ./public/build
COPY docker/railway-start.sh /usr/local/bin/railway-start

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/railway-start

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV PORT=8080

EXPOSE 8080

CMD ["railway-start"]
