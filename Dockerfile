# Use official PHP image
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set permissions for data directory
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html/data

# Expose port 80
EXPOSE 80

# Healthcheck (optional)
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 CMD curl -f http://localhost/ || exit 1

# Onreander/Render.io expects the container to start Apache
CMD ["apache2-foreground"]
