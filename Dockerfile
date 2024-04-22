FROM php:8.1-cli

# Environment
ENV PROJECT zdac-module

# Install system dependencies for ZIP handling
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install the PHP zip extension
RUN docker-php-ext-install zip

# Install required PHP extensions and RabbitMQ client
RUN docker-php-ext-install bcmath pdo pdo_mysql
RUN apt-get update && apt-get install -y librabbitmq-dev && pecl install amqp
RUN docker-php-ext-enable amqp

# Work Directory
# RUN mkdir -p /var/www/${PROJECT}
# WORKDIR /var/www/${PROJECT}
#
# COPY . .
COPY . /app
WORKDIR /app

# Install Composer dependencies
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# RUN mv composer.phar /usr/local/bin/composer
# RUN chmod +x /usr/local/bin/composer
# RUN composer install

# Use Composer to install dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# EXPOSE 8080
EXPOSE 9002
CMD ["php-fpm"]
# CMD ["php artisan serve --host=0.0.0.0 --port=8080"]
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9002"]
