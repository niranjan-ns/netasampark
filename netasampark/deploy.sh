#!/bin/bash

echo "🚀 NetaSampark - Political CRM Deployment Script"
echo "=================================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel project root directory"
    exit 1
fi

echo "📋 Checking system requirements..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 4 ]); then
    echo "❌ Error: PHP 8.4+ is required. Current version: $PHP_VERSION"
    exit 1
fi
echo "✅ PHP version: $PHP_VERSION"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Error: Composer is not installed"
    exit 1
fi
echo "✅ Composer is installed"

# Check Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Error: Node.js is not installed"
    exit 1
fi
echo "✅ Node.js is installed"

# Check npm
if ! command -v npm &> /dev/null; then
    echo "❌ Error: npm is not installed"
    exit 1
fi
echo "✅ npm is installed"

echo ""
echo "🔧 Installing dependencies..."

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
echo "Installing Node.js dependencies..."
npm ci --production

echo ""
echo "🏗️ Building frontend assets..."

# Build frontend assets
npm run build

echo ""
echo "⚙️ Configuring application..."

# Copy environment file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please configure your .env file with your database and service credentials"
fi

# Generate application key
if [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    echo "Generating application key..."
    php artisan key:generate
fi

echo ""
echo "🗄️ Setting up database..."

# Check if database connection is configured
if php artisan migrate:status &> /dev/null; then
    echo "Running database migrations..."
    php artisan migrate --force
    
    echo "Running database seeders..."
    php artisan db:seed --force
else
    echo "⚠️  Database connection not configured. Please update your .env file and run:"
    echo "   php artisan migrate"
    echo "   php artisan db:seed"
fi

echo ""
echo "🚀 Optimizing application..."

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear old caches
php artisan cache:clear
php artisan config:clear

echo ""
echo "🔐 Setting up storage..."

# Create storage links
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo ""
echo "📊 Setting up queues..."

# Check if Redis is available
if php artisan queue:work --once &> /dev/null; then
    echo "✅ Queue system is working"
else
    echo "⚠️  Queue system not configured. Please check Redis configuration"
fi

echo ""
echo "🌐 Starting web server..."

# Check if port 8000 is available
if ! lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "Starting development server on port 8000..."
    php artisan serve --host=0.0.0.0 --port=8000 &
    echo "✅ Server started at http://localhost:8000"
else
    echo "⚠️  Port 8000 is already in use. Please stop the existing server or use a different port"
fi

echo ""
echo "🎉 Deployment completed!"
echo ""
echo "📋 Next steps:"
echo "1. Configure your .env file with proper credentials"
echo "2. Set up your web server (nginx/apache) for production"
echo "3. Configure SSL certificates"
echo "4. Set up monitoring and logging"
echo "5. Configure backup systems"
echo ""
echo "🔗 Useful commands:"
echo "   php artisan serve --host=0.0.0.0 --port=8000  # Start development server"
echo "   php artisan queue:work                          # Process queues"
echo "   php artisan horizon                             # Start Horizon dashboard"
echo "   php artisan migrate:status                      # Check migration status"
echo "   php artisan route:list                          # List all routes"
echo ""
echo "📚 Documentation: https://docs.netasampark.com"
echo "🆘 Support: support@netasampark.com"
