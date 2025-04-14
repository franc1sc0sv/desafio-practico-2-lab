FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get install -y nodejs

RUN sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 25M/' /usr/local/etc/php/php.ini-development && \
    sed -i 's/^post_max_size = .*/post_max_size = 30M/' /usr/local/etc/php/php.ini-development

RUN sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 25M/' /usr/local/etc/php/php.ini-production && \
    sed -i 's/^post_max_size = .*/post_max_size = 30M/' /usr/local/etc/php/php.ini-production


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install
RUN npm install

CMD ["npm", "run", "watch"]
