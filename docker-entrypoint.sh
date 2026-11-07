#!/bin/sh
set -e

cd /var/www

echo "Bootstrapping Laravel..."

# -----------------------------------
# 1. Install Laravel if missing
# -----------------------------------
if [ ! -f artisan ]; then
    echo "Laravel not found. Installing..."

    rm -rf temp

    composer create-project laravel/laravel temp

    echo "Moving Laravel files..."

    mv temp/* .
    mv temp/.[!.]* . 2>/dev/null || true

    rm -rf temp
fi

# -----------------------------------
# 2. Apply Docker .env only if .env is missing or empty
# -----------------------------------
[ -s .env ] || cp .env.docker .env

# -----------------------------------
# 3. Install dependencies
# -----------------------------------
if [ ! -d vendor ]; then
    echo "Installing dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# -----------------------------------
# 4. Generate app key
# -----------------------------------
if ! grep -q "^APP_KEY=base64" .env; then
    echo "Generating app key..."
    php artisan key:generate --force
fi

# -----------------------------------
# 5. Wait for MySQL
# -----------------------------------
echo "Waiting for MySQL..."

until php -r "
try {
    new PDO('mysql:host=mysql;dbname=communecircles', 'user', 'password');
    echo 'DB connected\n';
} catch (Exception \$e) {
    exit(1);
}
"; do
    sleep 2
done

# -----------------------------------
# 6. Run migrations (background)
# -----------------------------------
(
    echo "Running migrations..."
    php artisan migrate --force || true

    echo "Clearing config cache..."
    php artisan config:clear || true
) &

# -----------------------------------
# 7. Start PHP-FPM
# -----------------------------------
echo "Starting PHP-FPM..."
exec php-fpm -F
