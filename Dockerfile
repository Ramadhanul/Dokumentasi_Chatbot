FROM php:8.2-apache

# Install system deps
RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libonig-dev libxml2-dev curl npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Enable apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy project
COPY . .

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
