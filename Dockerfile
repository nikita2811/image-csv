# Stage 1: Node build
FROM node:20 AS frontend

WORKDIR /var/www

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build   # creates public/build/

# Stage 2: PHP backend
FROM php:8.2-fpm-alpine AS backend

WORKDIR /var/www

RUN apk add --no-cache \
    git unzip curl libpng-dev libjpeg-turbo-dev libwebp-dev libxpm-dev freetype-dev \
    oniguruma-dev libxml2-dev zip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
RUN composer install --no-dev --optimize-autoloader

# Copy Vue build
COPY --from=frontend /var/www/public/build /var/www/public/build

EXPOSE 9000
CMD ["php-fpm"]
