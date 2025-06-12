FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && \
    apt-get install -y \
        libcurl4-openssl-dev \
        curl \
        && \
    docker-php-ext-install curl && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache modules and configure
RUN a2enmod rewrite headers && \
    echo "ServerTokens Prod" >> /etc/apache2/apache2.conf && \
    echo "ServerSignature Off" >> /etc/apache2/apache2.conf

# Configure Apache virtual host directly in Dockerfile
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory "/var/www/html">\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes\n\
        Header always set X-Content-Type-Options nosniff\n\
        Header always set X-Frame-Options DENY\n\
        Header always set X-XSS-Protection "1; mode=block"\n\
        Header always set Referrer-Policy "strict-origin-when-cross-origin"\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Configure PHP for sessions
RUN mkdir -p /var/lib/php/sessions && \
    chown -R www-data:www-data /var/lib/php/sessions && \
    chmod 755 /var/lib/php/sessions && \
    echo "session.save_path = \"/var/lib/php/sessions\"" >> /usr/local/etc/php/conf.d/sessions.ini && \
    echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/sessions.ini && \
    echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/sessions.ini && \
    echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/security.ini

# Copy ALL application files into the image
COPY ./ /var/www/html/

# Create .htaccess for security directly in the image
RUN echo 'Options -Indexes\n\
<Files "*.log">\n\
    Require all denied\n\
</Files>\n\
<Files "*.md">\n\
    Require all denied\n\
</Files>\n\
<IfModule mod_headers.c>\n\
    Header always set X-Content-Type-Options nosniff\n\
    Header always set X-Frame-Options DENY\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
</IfModule>' > /var/www/html/.htaccess

# Set proper permissions for everything
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
