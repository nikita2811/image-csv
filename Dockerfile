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
    git unzip curl libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev libxpm-dev \
    libonig-dev libxml2-dev zip libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
RUN composer install --no-dev --optimize-autoloader

# Copy Vue build
COPY --from=frontend /var/www/public/build /var/www/public/build

EXPOSE 9000
CMD ["php-fpm"]
