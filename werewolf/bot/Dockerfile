FROM php:8.1-apache

RUN a2enmod rewrite

# کپی همه چیز به apache
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

RUN mkdir -p /var/www/html/data && chmod 777 /var/www/html/data

EXPOSE 80
