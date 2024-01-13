FROM php:8.2-fpm

RUN apt update
RUN apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip
RUN docker-php-ext-install intl opcache pdo pdo_mysql
RUN pecl install apcu
RUN docker-php-ext-enable apcu
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip

RUN chmod -R 777 /home

WORKDIR /home/refactorisation_projet

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
RUN mkdir /.symfony5 \
	&& chown 1000:1000 /.symfony5
