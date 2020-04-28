FROM php:7.4-fpm

RUN apt update -y && \
    apt install -y netcat git libzip-dev zip libcurl4-openssl-dev pkg-config libssl-dev wget

# PHP
RUN docker-php-ext-install sockets zip pdo_mysql bcmath opcache
RUN pecl install redis-5.1.1
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Symfony
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# NPM
RUN apt install -y npm yarn
RUN npm install -g yarn

# Nginx
RUN mkdir -p /var/www/html
RUN apt install -y nginx
RUN chown -R www-data:www-data /var/www/html

COPY api.conf /etc/nginx/sites-available/default

RUN service nginx start
RUN usermod -u 1000 www-data
WORKDIR /var/www/html

EXPOSE 80
EXPOSE 9000
