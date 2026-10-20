# ============================================================
# DOCKERFILE - PHP 8.2 + Apache para Despedida de Solteros
# ============================================================

FROM php:8.2-apache

# Actualizar repositorios e instalar dependencias básicas del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer globalmente dentro del contenedor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar Apache VirtualHost
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Ajustar permisos para Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
