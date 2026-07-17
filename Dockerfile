FROM php:8.2-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libcurl4-openssl-dev \
	&& docker-php-ext-install curl \
	&& rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
