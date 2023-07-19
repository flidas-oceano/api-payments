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
RUN docker-php-ext-install gd

# Change current user to root
USER root

# Copy existing application directory permissions
RUN usermod -u 1000 www-data

WORKDIR /var/www

#COPY . /var/www/api-payments
#COPY ../../laravel-foclis /var/www/laravel-foclis

#RUN cd /var/www/api-payments && /usr/local/bin/composer install
#RUN cd /var/www/laravel-foclis && /usr/local/bin/composer install

#RUN chown www-data:www-data -R /var/www/api-payments && chmod 755 -R /var/www/api-payments/storage
#RUN chown www-data:www-data -R /var/www/laravel-foclis && chmod 755 -R /var/www/laravel-foclis/storage

#RUN php /var/www/api-payments/artisan migrate --seed
#RUN php /var/www/laravel-foclis/artisan migrate --seed

# Expose port 9000 and start php-fpm server
CMD ["php-fpm"]

EXPOSE 9000
