FROM php:8.1-apache

# فعال کردن rewrite
RUN a2enmod rewrite

# کپی فایل‌ها
COPY . /var/www/html/

# تنظیم دسترسی‌ها
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# ایجاد پوشه data با دسترسی نوشتن
RUN mkdir -p /var/www/html/data && chmod 777 /var/www/html/data

# تنظیم index.php به عنوان فایل پیش‌فرض
RUN echo "DirectoryIndex index.php" > /etc/apache2/conf-available/dir.conf && \
    a2enconf dir

EXPOSE 80
