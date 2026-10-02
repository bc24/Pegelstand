# Pegelstand als Container (Apache + PHP). Aus dem Quellcode gebaut, siehe docs/docker.md.
# Ungetestet: In der Entwicklungsumgebung stand kein Docker zur Verfügung (TODO(prüfen)).

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY bin ./bin
COPY resources ./resources
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
COPY src ./src
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/pegelstand.conf \
    && a2enconf pegelstand
WORKDIR /var/www/html
COPY index.php .htaccess LICENSE README.md CHANGELOG.md THIRD-PARTY.md ./
COPY src ./src
COPY database ./database
COPY resources ./resources
COPY licenses ./licenses
COPY docs ./docs
COPY config/.htaccess config/config.example.php ./config/
COPY storage/.htaccess ./storage/
COPY bin/.htaccess bin/cron.php ./bin/
COPY --from=assets /app/assets ./assets
COPY --from=vendor /app/vendor ./vendor
COPY src/.htaccess ./vendor/.htaccess
# Konfiguration und Daten liegen in Volumes (siehe docker-compose.yml), der Webserver braucht Schreibrechte darauf.
RUN mkdir -p storage config && chown -R www-data:www-data config storage
VOLUME ["/var/www/html/config", "/var/www/html/storage"]
EXPOSE 80
