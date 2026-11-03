#!/bin/sh

# Jalankan migrasi database otomatis saat web menyala
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Nyalakan PHP-FPM di background
php-fpm -D

# Nyalakan Nginx di foreground
nginx -g "daemon off;"
