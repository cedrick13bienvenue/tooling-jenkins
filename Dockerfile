FROM php:8.2-apache
LABEL maintainer="dare@zooto.io"

ENV PORT=80

RUN docker-php-ext-install mysqli
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
COPY start-apache /usr/local/bin
RUN a2enmod rewrite

COPY html /var/www
RUN chown -R www-data:www-data /var/www

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -f http://localhost/ || exit 1

CMD ["start-apache"]