#!/bin/bash

# chmod +x setup.sh
# Script for initial setup of the cloned project

set -e  # Stop script if any command fails

echo "================================================"
echo "  La Maison Reservations - Initial Setup"
echo "================================================"
echo ""

# 1. Check if .env.local exists
echo "Step 1: Checking .env.local file..."
if [ ! -f .env.local ]; then
    echo "⚠️  .env.local doesn't exist! Creating example..."
    cat > .env.local << 'EOF'
# Default environment variables for La Maison Reservations
DATABASE_URL="mysql://root:pass1234@mysql:3306/la_maison_reservations_dev?serverVersion=8.0.39&charset=utf8mb4"
APP_ENV=dev
APP_DEBUG=true
EOF
    echo "✓ Created .env.local with default values"
    echo "  Please review and adjust DATABASE_URL if needed!"
    echo ""
else
    echo "✓ .env.local already exists"
    echo ""
fi

# 2. Docker compose up --build
echo "Step 2: Starting Docker containers..."
docker compose up --build -d
echo "✓ Docker containers started"
echo ""

# 3. Composer install
echo "Step 3: Installing Composer dependencies..."
if [ ! -d "vendor" ]; then
    docker compose exec php composer update
    echo "✓ Composer dependencies installed"
else
    echo "✓ Vendor folder already exists (skipping)"
fi
echo ""

# Wait for MySQL to be ready
echo "Step 4: Waiting for MySQL to be ready..."
until docker compose exec mysql mysqladmin ping -h "localhost" -u root -ppass1234 --silent; do
    echo "Waiting for MySQL..."
    sleep 2
done
echo "✓ MySQL is ready"
echo ""

# 4. Database creation
echo "Step 5: Creating database..."
docker compose exec php bin/console doctrine:database:create --if-not-exists
echo "✓ Database created (or already exists)"
echo ""

# 5. Migrations
echo "Step 6: Running migrations..."
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
echo "✓ Migrations executed"
echo ""

# 6. Create test database
echo "Step 7: Creating test database..."
docker compose exec mysql mysql -uroot -ppass1234 -e "CREATE DATABASE IF NOT EXISTS \`la_maison_reservations_test\`;"
echo "✓ Test database created (or already exists)"
echo ""

# 7. Run migrations on test database
echo "Step 8: Running migrations on test database..."
docker compose exec mysql sh -c "mysqldump -uroot -ppass1234 --no-data la_maison_reservations_dev | mysql -uroot -ppass1234 la_maison_reservations_test"
echo "✓ Test database schema synchronized"
echo ""

# 8. Load fixtures (seed initial data)
echo "Step 9: Loading initial data (time slots, tables)..."
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
docker compose exec -e APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction
echo "✓ Initial data loaded"
echo ""

# 9. Asset compile
echo "Step 10: Compiling assets..."
docker compose exec php bin/console asset-map:compile
echo "✓ Assets compiled"
echo ""

# 10. Clear cache (optional but recommended)
echo "Step 11: Clearing cache..."
docker compose exec php bin/console cache:clear
echo "✓ Cache cleared"
echo ""

echo "================================================"
echo "  ✅ Setup completed successfully!"
echo "================================================"
echo ""
echo "Application is available at:"
echo "  🌐 Web: http://localhost:8080"
echo "  🗄️  phpMyAdmin: http://localhost:8090"
echo ""
echo "Useful commands:"
echo "  ./up.sh                    - Start containers"
echo "  ./down.sh                  - Stop containers"
echo "  ./migrations-migrate.sh    - Run migrations"
echo "  ./asset-map-compile.sh     - Compile assets"
echo "  ./test.sh                  - Run tests"
echo "  ./clear-cache.sh           - Clear cache"
echo "  ./qa.sh                    - Run code quality checks"
echo ""
