FROM mike/php_api_base:0.10.0

WORKDIR /app

COPY ./composer.json ./composer.json
COPY ./composer.lock ./composer.lock
RUN mkdir ./tests; mkdir ./database;

RUN composer install

COPY . .

RUN composer dump-autoload

RUN chown -R application:application /app

ENTRYPOINT ["./docker-entrypoint.sh"]
