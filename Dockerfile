FROM php:8.1.25-fpm-alpine3.18

# Environment
ENV PROJECT = "zdac-module"

# Work Directory
RUN mkdir -p /go/src/${PROJECT}
WORKDIR /go/src/${PROJECT}

COPY . .

EXPOSE 8080

CMD ["php artisan serve --host=0.0.0.0 --port=8080"]
