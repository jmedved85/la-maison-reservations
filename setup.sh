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
###> doctrine/doctrine-bundle ###
# Set DATABASE_URL with your database name
DATABASE_URL="mysql://root:pass1234@mysql:3306/la_maison_reservations_dev?serverVersion=8.0.39&charset=utf8mb4"
###< doctrine/doctrine-bundle ###

###> symfony/mailer ###
# Mailer configuration (optional - mailpit)
# MAILER_DSN=smtp://mailpit:1025
###< symfony/mailer ###
EOF
    echo "✓ Created .env.local with default values"
    echo "  Please review and adjust DATABASE_URL if needed!"
    echo ""
else
    echo "✓ .env.local already exists"
    echo ""
fi

# 2. Composer install
echo "Step 2: Composer install..."
if [ ! -d "vendor" ]; then
    composer install
    echo "✓ Composer dependencies installed"
else
    echo "✓ Vendor folder already exists (skipping)"
fi
echo ""

# 3. Docker compose up --build
echo "Step 3: Starting Docker containers..."
docker compose up --build -d
echo "✓ Docker containers started"
echo ""

# Wait for MySQL to be ready
echo "Step 4: Waiting for MySQL to be ready..."
sleep 10
echo "✓ MySQL should be ready"
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

# 6. Asset compile
echo "Step 7: Compiling assets..."
docker compose exec php bin/console asset-map:compile
echo "✓ Assets compiled"
echo ""

# 7. Clear cache (optional but recommended)
echo "Step 8: Clearing cache..."
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
echo ""
