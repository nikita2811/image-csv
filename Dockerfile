# Stage 1: Node build
FROM node:20 AS frontend

WORKDIR /var/www

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build   # creates dist/

# Stage 2: PHP backend
FROM php:8.2-fpm AS backend

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
RUN composer install --no-dev --optimize-autoloader

# Copy Vue build
COPY --from=frontend /var/www/dist /var/www/public

EXPOSE 9000
CMD ["php-fpm"]
