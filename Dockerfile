# =====================================================================
# SDGT — imagem da aplicação (Laravel + Apache + drivers SQL Server)
# Um único artefato serve os papéis: app (web), worker (fila) e scheduler,
# selecionados pela variável CONTAINER_ROLE (ver docker/entrypoint.sh).
# Observação: o driver ODBC da Microsoft é publicado para linux/amd64.
# =====================================================================
FROM php:8.3-apache-bookworm

ENV ACCEPT_EULA=Y \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="$PATH:/opt/mssql-tools18/bin"

# Dependências de sistema + Microsoft ODBC Driver 18 + extensões PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        gnupg2 curl ca-certificates unzip git libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev unixodbc-dev \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list -o /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update && apt-get install -y --no-install-recommends msodbcsql18 mssql-tools18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" zip gd intl bcmath opcache pcntl \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && apt-get purge -y --auto-remove gnupg2 && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/php.ini /usr/local/etc/php/conf.d/sdgt.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências PHP primeiro (melhor uso de cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD curl -fs http://localhost/up || exit 1
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
