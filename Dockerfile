FROM php:8.2-cli

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Instala Node.js LTS y npm
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get install -y nodejs

# Instala nodemon globalmente (puedes usar local + npx si prefieres)
RUN npm install -g nodemon

# Ajustes de configuración de PHP
RUN sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 25M/' /usr/local/etc/php/php.ini-development && \
    sed -i 's/^post_max_size = .*/post_max_size = 30M/' /usr/local/etc/php/php.ini-development && \
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 25M/' /usr/local/etc/php/php.ini-production && \
    sed -i 's/^post_max_size = .*/post_max_size = 30M/' /usr/local/etc/php/php.ini-production

# Copia Composer desde imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos del proyecto
COPY . /var/www/html

COPY .env /var/www/html/.env

# Instala dependencias de PHP y Node.js
RUN composer install
RUN npm install

# Ejecuta el comando al iniciar el contenedor
CMD ["npm", "run", "watch"]
