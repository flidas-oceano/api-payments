FROM php:7.4.33-fpm


RUN apt-get update && apt-get install -y \
    build-essential \
    apt-utils \
    curl \
    zip \
    bash \
    default-mysql-client \
    unzip \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libzip-dev

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install extensions
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip

# Change current user to root
USER root

# Copy existing application directory permissions
RUN usermod -u 1000 www-data

WORKDIR /var/www

# Expose port 9000 and start php-fpm server
CMD ["php-fpm"]

EXPOSE 9000
