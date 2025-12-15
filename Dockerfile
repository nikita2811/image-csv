# Stage 1: Node build
FROM node:20 AS frontend

WORKDIR /var/www

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build   # creates public/build/

# Stage 2: PHP backend
FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev supervisor sqlite-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_sqlite \
    && apk del libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libxml2-dev sqlite-dev

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . /var/www/html

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Configure environment
RUN cp .env.example .env && \
    echo 'DB_DATABASE=/var/www/html/database/database.sqlite' >> .env

# Create SQLite database file
RUN touch database/database.sqlite && chown www-data:www-data database/database.sqlite

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Generate application key
RUN php artisan key:generate

# Run migrations
RUN php artisan migrate --force


EXPOSE 9000
CMD ["/usr/bin/supervisord"]


