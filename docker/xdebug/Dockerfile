FROM keboola/no-code-dbt-transformation

WORKDIR /code/

COPY php-xdebug.ini /usr/local/etc/php/php.ini

RUN pecl install xdebug \
  && docker-php-ext-enable xdebug