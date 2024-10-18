# Dockerfile

# Verwende ein PHP-Image
FROM php:8.1-apache

# Installiere Abhängigkeiten, falls benötigt
RUN docker-php-ext-install pdo pdo_mysql

# Kopiere die php_uploads.ini in den Ordner für benutzerdefinierte PHP.ini-Dateien
COPY php_uploads.ini /usr/local/etc/php/conf.d/

# Kopiere die Anwendungsdateien in das Containerverzeichnis
COPY . /var/www/html/

# Setze das Arbeitsverzeichnis
WORKDIR /var/www/html

# Stelle sicher, dass Apache die richtigen Berechtigungen hat
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
