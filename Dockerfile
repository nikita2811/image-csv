# Stage 1: Build PHP backend
FROM php:8.2-fpm AS backend

WORKDIR /var/www

# Install PHP dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Laravel project files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Build Vue frontend
FROM node:20 AS frontend

WORKDIR /var/www

COPY . .

# Install Node dependencies & build assets
RUN npm install
RUN npm run build

# Stage 3: Final image with Nginx
FROM nginx:alpine

COPY --from=backend /var/www /var/www
COPY --from=frontend /var/www/dist /var/www/public

# Copy custom nginx config
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
