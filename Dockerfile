FROM php:5-apache

RUN apt-get update \
    && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng12-dev \
        sqlite3 libsqlite3-dev \
        libssl-dev \
        git \
    && pecl install mongo \
    && docker-php-ext-install -j$(nproc) iconv gd pdo pdo_sqlite \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && a2enmod rewrite

RUN echo "extension=mongo.so" > /usr/local/etc/php/conf.d/mongo.ini

COPY cockpit-docker-config.php /var/www/html/custom/config.php
# Next branch configuration
COPY cockpit-docker-config.php /var/www/html/config/config.php
COPY cockpit /var/www/html/
RUN chmod 777 -R storage config

USER www-data
COPY site /frontend
COPY github-ssh-key/id_rsa /github-ssh-key/id_rsa
RUN \
  eval $(ssh-agent) && \
  chmod 600 /github-ssh-key/id_rsa && \
  mkdir ~/.ssh && \
  # Host key verification failed: http://stackoverflow.com/a/27488865
  ssh-keyscan -t rsa github.com > ~/.ssh/known_hosts && \
  echo "IdentityFile /github-ssh-key/id_rsa" >> /etc/ssh/ssh_config && \
  mkdir /frontend/src && \
  cd /frontend/src && \
  git clone git@github.com:ff-wartberg/ff-wartberg.github.io.git /frontend/src && \
  git config user.email "bernhard@mayr.io" && \
  git config user.name "Bernhard Mayr"

VOLUME /var/www/html/storage
