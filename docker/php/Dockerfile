FROM php:8.4-fpm

# Install system deps and utilities (bash, git, unzip etc.) and PHP extensions
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev \
    libxml2-dev zip unzip libpq-dev sed bash && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd || true

# Install Composer (copy from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy entrypoint into image (path relative to build context) and normalize CRLF->LF
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN if command -v sed >/dev/null 2>&1; then sed -i 's/\r$//' /usr/local/bin/entrypoint.sh || true; fi \
 && chmod +x /usr/local/bin/entrypoint.sh

# Copy application files from the repo root 'src' directory into the image
COPY src /var/www

RUN chown -R www-data:www-data /var/www || true

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
