FROM php:7.4-cli

RUN apt update -y && \
    apt install -y netcat git libzip-dev zip libcurl4-openssl-dev pkg-config libssl-dev wget

RUN docker-php-ext-install sockets zip pdo_mysql bcmath opcache
RUN pecl install redis-5.1.1

RUN mkdir -p /var/www

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Symfony
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony/bin/symfony /usr/local/bin/symfony
