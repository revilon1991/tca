FROM php:7.2-fpm

ENV TZ=Europe/Moscow

RUN apt-get update && apt-get install -y \
        curl \
        wget \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        zlib1g-dev \
        libicu-dev \
        g++ \
        iputils-ping \
        nano \
        libgmp-dev \
        re2c \
        libmhash-dev \
        file \
    && docker-php-ext-install -j$(nproc) intl iconv mbstring mysqli pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

RUN ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/local/include/
RUN docker-php-ext-configure gmp
RUN docker-php-ext-install gmp

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD php.ini /usr/local/etc/php/conf.d/40-custom.ini

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "Europe/Moscow"\n' > /usr/local/etc/php/conf.d/tzone.ini
RUN export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y tzdata \
    && dpkg-reconfigure --frontend noninteractive tzdata

WORKDIR /var/www

CMD ["php-fpm"]