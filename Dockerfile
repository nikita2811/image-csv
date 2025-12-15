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
    oniguruma-dev libxml2-dev supervisor sqlite-dev nginx \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_sqlite \
    && apk del libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libxml2-dev sqlite-dev

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy built frontend assets from Node stage
COPY --from=frontend /var/www/public/build /var/www/html/public/build

# Copy application code
COPY . /var/www/html

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy nginx config
COPY nginx/default.conf /etc/nginx/http.d/default.conf

# Configure PHP-FPM to listen on TCP
RUN sed -i 's/^listen = .*/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf

# Configure environment (before composer install)
RUN cp .env.example .env && \
    echo 'DB_DATABASE=/var/www/html/database/database.sqlite' >> .env

# Generate application key (needed for composer package:discover)
RUN php artisan key:generate

# Create SQLite database file
RUN touch database/database.sqlite && chown www-data:www-data database/database.sqlite

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Run migrations
RUN php artisan migrate --force

# Clear caches and optimize
RUN php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize

EXPOSE 80 9000
CMD ["/usr/bin/supervisord"]


