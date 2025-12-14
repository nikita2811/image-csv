# Stage 1: Node build
FROM node:20 AS frontend

WORKDIR /var/www

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build   # creates public/build/

# Stage 2: PHP backend
FROM php:8.2-fpm AS backend

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev zip libzip-dev supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip xml pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application
COPY . .

# Create SQLite database and run migrations
RUN mkdir -p database && touch database/database.sqlite && php artisan migrate --force

# Copy Vue build
COPY --from=frontend /var/www/public/build /var/www/public/build

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000
CMD ["/usr/bin/supervisord"]
