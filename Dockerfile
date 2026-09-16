FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl opcache zip gd \
    && a2dismod -f mpm_event mpm_worker \
    && a2enmod mpm_prefork \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Point Apache at Symfony public/ without rewriting unrelated conf files (that broke MPM).
RUN printf '%s\n' \
    '<VirtualHost *:${PORT}>' \
    '    ServerAdmin webmaster@localhost' \
    '    DocumentRoot /var/www/html/public' \
    '    <Directory /var/www/html/public>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '        Options FollowSymLinks' \
    '        FallbackResource /index.php' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/^Listen .*/Listen ${PORT}/' /etc/apache2/ports.conf || true

# PORT is substituted at container start by the entrypoint; bake a default for build sanity.
ENV PORT=8080
RUN sed -i 's/\${PORT}/8080/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/^Listen .*/Listen 8080/' /etc/apache2/ports.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN APP_SECRET=build-time-only \
    DATABASE_URL="mysql://root@127.0.0.1:3306/app?serverVersion=9.4.0&charset=utf8mb4" \
    composer install --no-dev --optimize-autoloader --no-interaction \
    && APP_SECRET=build-time-only \
       DATABASE_URL="mysql://root@127.0.0.1:3306/app?serverVersion=9.4.0&charset=utf8mb4" \
       php bin/console importmap:install --env=prod \
    && APP_SECRET=build-time-only \
       DATABASE_URL="mysql://root@127.0.0.1:3306/app?serverVersion=9.4.0&charset=utf8mb4" \
       php bin/console asset-map:compile --env=prod \
    && mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var \
    && rm -rf public/_adminer.php public/adminer.php public/adminer-plugins.php public/adminer-plugins

COPY docker/railway-entrypoint.sh /usr/local/bin/railway-entrypoint.sh
RUN chmod +x /usr/local/bin/railway-entrypoint.sh

ENTRYPOINT ["railway-entrypoint.sh"]
