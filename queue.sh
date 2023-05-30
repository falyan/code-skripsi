#!/bin/sh

cd /var/www/html
php artisan queue:work --queue=high,default,low --tries=5
