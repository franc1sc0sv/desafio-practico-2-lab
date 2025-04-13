FROM php:8.2-cli

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    entr \
    && docker-php-ext-install pdo pdo_pgsql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la app
COPY . /var/www/html

# Instalar dependencias PHP (vía Composer)
RUN composer install

# Copiar los scripts necesarios
COPY start.sh /usr/local/bin/start.sh
COPY watch.sh /usr/local/bin/watch.sh

# Dar permisos de ejecución
RUN chmod +x /usr/local/bin/start.sh /usr/local/bin/watch.sh

# Ejecutar el script de inicio con hot reload
CMD ["sh", "/usr/local/bin/start.sh"]
