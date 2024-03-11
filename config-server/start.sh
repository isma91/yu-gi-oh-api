#!/bin/sh

# Launch PHP-FPM
php-fpm82 -D

# Start Nginx
nginx -g "daemon off;"

composer install