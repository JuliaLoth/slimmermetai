# Multi-stage build for SlimmerMetAI website
FROM node:18-alpine AS frontend-builder

# Set working directory
WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Clear npm cache and remove lock file for fresh install
RUN npm cache clean --force && rm -f package-lock.json

# Install npm dependencies
RUN npm install --only=production --ignore-scripts

# Copy source code
COPY . .

# Build frontend assets
RUN npm run build

# Production PHP stage
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    git \
    curl \
    zip \
    unzip \
    jpeg-dev \
    libpng-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        gd \
        opcache \
        intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure opcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy built frontend assets from previous stage
COPY --from=frontend-builder --chown=www-data:www-data /app/public_html/assets ./public_html/assets

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories
RUN mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/logs \
    && chmod -R 755 /var/www/html/uploads

# Configure nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Configure supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure PHP-FPM
RUN echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/www.conf

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 