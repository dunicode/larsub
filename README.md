crontab -e
    0 3 * * * cd /ruta/completa/a/tu/proyecto-laravel && php artisan subscriptions:check-status >> /ruta/completa/a/tu/proyecto-laravel/storage/logs/subscription-check.log 2>&1


pwd

# Ejemplo de ruta completa:
# /home/tu_usuario/public_html/tu-proyecto
# /var/www/html/tu-proyecto
