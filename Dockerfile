# Laravel API + Filament Admin Dockerfile
FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    postgresql-client \
    netcat-openbsd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка Node.js для сборки фронтенда (Filament, Vue.js)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Рабочая директория
WORKDIR /var/www

# Копирование файлов
COPY . .

# Установка зависимостей PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Установка зависимостей Node.js и сборка фронтенда
RUN if [ -f package.json ]; then npm install && npm run build; fi

# Права доступа
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Создание .env если не существует
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Копируем entrypoint скрипт
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
