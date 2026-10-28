# Production image: PHP 8.3 + Apache, document root = public/
FROM php:8.3-apache

RUN a2enmod rewrite headers expires deflate \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && docker-php-ext-install opcache \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { echo 'opcache.enable=1'; echo 'opcache.validate_timestamps=0'; echo 'expose_php=Off'; } > "$PHP_INI_DIR/conf.d/app.ini" \
    && echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf \
    && echo 'ServerSignature Off' >> /etc/apache2/apache2.conf

COPY --chown=www-data:www-data . /var/www/html

ENV APP_ENV=prod
EXPOSE 80
