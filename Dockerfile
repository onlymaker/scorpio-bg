FROM syncxplus/php:7.3.8-cli-stretch

WORKDIR /data/

COPY . ./

ENTRYPOINT ["docker-php-entrypoint"]

CMD ["php", "index.php"]
