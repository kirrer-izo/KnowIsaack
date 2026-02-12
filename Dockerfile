FROM php:8.2-apache

# Enable mod_rewrite for .htaccess
RUN a2enmod rewrite

# Install system dependencies for Composer and PHP extensions
RUN apt-get update && apt-get install -y \
    git\
    unzip\
    libpng-dev\
    && docker-php-ext-install gd mysqli pdo pdo_mysql

#Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy custom Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copy application source
COPY . /var/www/html/

#Install the Resend PHP SDK using Composer
RUN composer require resend/resend-php

# Set permissions for the web user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html


