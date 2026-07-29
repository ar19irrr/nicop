FROM php:8.1-apache

RUN a2enmod rewrite

# کپی کردن همه فایل‌ها به مسیر apache
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

RUN mkdir -p /var/www/html/data && chmod 777 /var/www/html/data

# تنظیم DocumentRoot به پوشه bot
RUN echo "DocumentRoot /var/www/html" > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
