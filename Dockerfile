FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libpng-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl opcache zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n    Options FollowSymLinks\n</Directory>\n' \
        > /etc/apache2/conf-available/symfony-public.conf \
    && a2enconf symfony-public

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Runtime secrets (APP_SECRET, DATABASE_URL) come from Railway Variables.
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DATABASE_URL="mysql://root@127.0.0.1:3306/app?serverVersion=8.0&charset=utf8mb4"

# APP_SECRET is only needed so Symfony console can boot during image build.
RUN APP_SECRET=build-time-only \
    composer install --no-dev --optimize-autoloader --no-interaction \
    && APP_SECRET=build-time-only php bin/console importmap:install --env=prod \
    && APP_SECRET=build-time-only php bin/console asset-map:compile --env=prod \
    && mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var \
    && rm -rf public/_adminer.php public/adminer.php public/adminer-plugins.php public/adminer-plugins

COPY docker/railway-entrypoint.sh /usr/local/bin/railway-entrypoint.sh
RUN chmod +x /usr/local/bin/railway-entrypoint.sh

ENTRYPOINT ["railway-entrypoint.sh"]
