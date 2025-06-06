# Multi-stage build for SlimmerMetAI website
FROM node:18-alpine AS frontend-builder

# Set working directory
WORKDIR /app

# Install dependencies needed for native builds
RUN apk add --no-cache python3 make g++ libc6-compat

# Copy package.json and package-lock.json
COPY package*.json ./

# Clean install with better handling of optional dependencies
# Force install the correct rollup binary for linux
RUN npm cache clean --force && \
    rm -f package-lock.json && \
    npm install --no-optional && \
    npm install @rollup/rollup-linux-x64-gnu --save-optional || true

# Copy source code
COPY . .

# Build frontend assets with multiple fallback strategies
RUN npm run build || \
    (echo "Build failed, trying with ROLLUP_BINARY_PATH..." && \
     export ROLLUP_BINARY_PATH=/app/node_modules/@rollup/rollup-linux-x64-gnu/rollup.linux-x64-gnu.node && \
     npm run build) || \
    (echo "Build failed, trying with legacy peer deps..." && \
     npm install --legacy-peer-deps && \
     npm run build) || \
    (echo "Build failed, using JS fallback..." && \
     export ROLLUP_FORCE_JS=1 && \
     npm run build) || \
    (echo "All builds failed, using manual fallback..." && \
     npm run build-fallback)

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
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        gd \
        opcache \
        intl \
        zip

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy built frontend assets from frontend-builder stage
COPY --from=frontend-builder /app/public_html/assets/ ./public_html/assets/

# Create necessary directories
RUN mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/uploads \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/logs \
    && chmod -R 755 /var/www/html/uploads

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 