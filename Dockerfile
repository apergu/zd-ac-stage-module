FROM php:8.1.25-cli

# Environment
ENV PROJECT = "zdac-module"

# Work Directory
RUN mkdir -p /var/www/${PROJECT}
WORKDIR /var/www/${PROJECT}

COPY . .

EXPOSE 8080

CMD ["php -v", "/usr/bin/php -v",  "/usr/bin/php /var/www/${PROJECT}/artisan serve --host=0.0.0.0 --port=8080"]
