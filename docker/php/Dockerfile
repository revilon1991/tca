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
        re2c \
        libmhash-dev \
        file \
        supervisor \
###> tensorflow ###
        imagemagick \
        python3 \
        python3-pip \
###< tensorflow ###
    && docker-php-ext-install -j$(nproc) intl iconv mbstring mysqli pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD php.ini /usr/local/etc/php/conf.d/40-custom.ini

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "Europe/Moscow"\n' > /usr/local/etc/php/conf.d/tzone.ini
RUN export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y tzdata \
    && dpkg-reconfigure --frontend noninteractive tzdata

###> tensorflow ###
RUN pip3 install --upgrade pip
RUN pip3 install tensorflow
RUN pip3 install 'setuptools>=41.0.0'
RUN pip3 install 'numpy==1.16.4'
###< tensorflow ###

RUN docker-php-ext-install pcntl

WORKDIR /app

CMD cp config/supervisor/supervisor.conf /etc/supervisor/conf.d/ && \
    sed -i -e 's/__RELEASE_CONSOLE_PATH__/\/app\/bin\/console/g' /etc/supervisor/conf.d/supervisor.conf && \
    service supervisor start && \
    php-fpm
