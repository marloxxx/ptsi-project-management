# Setup Guide - Laravel Starter Kit PTSI

## 🚀 Quick Setup (Production Ready)

### 1. Initial Setup

```bash
# Clone & Install
git clone https://github.com/ptsi-digital/laravel-starter-kit-ptsi.git
cd laravel-starter-kit-ptsi
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Configure .env
# Edit DB_* variables untuk database Anda
```

### 2. Database Configuration

Edit `.env`:

```env
APP_NAME="PTSI Admin"
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_starter_ptsi
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Run Migrations & Seed

```bash
# Run migrations dengan seeders
php artisan migrate --seed

# Seeder akan membuat:
# - Roles: super_admin, admin, manager, viewer
# - Users: admin@ptsi.co.id (password: password)
```

### 4. Generate Permissions

```bash
# Generate permissions untuk semua resources
php artisan shield:generate --all

# Assign super admin role
php artisan shield:super-admin
# Pilih user: admin@ptsi.co.id
```

### 5. Build Assets & Run

```bash
# Build frontend assets
npm run build

# Run development server
php artisan serve

# Or use the dev script (recommended)
composer run dev
```

Visit: **http://localhost:8000/admin**

**Login:**
- Email: `admin@ptsi.co.id`
- Password: `password`

---

## 📖 API Documentation (Scramble)

API documentation auto-generated via Scramble.

```bash
# Publish Scramble config (optional)
php artisan vendor:publish --tag=scramble-config
```

Visit: **http://localhost:8000/docs/api**

---

## 🐛 Debug Mode

### Development

Enable debug bar dalam development:

```env
APP_DEBUG=true
DEBUGBAR_ENABLED=true
```

### Production

**IMPORTANT**: Disable debug di production!

```env
APP_DEBUG=false
DEBUGBAR_ENABLED=false
```

---

## 🐳 Docker Setup (Laravel Sail)

```bash
# Install Sail
composer require laravel/sail --dev
php artisan sail:install

# Start containers
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate --seed

# Access application
http://localhost
```

---

## 🧪 Testing

### Run Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=UserServiceTest

# With coverage
php artisan test --coverage
```

### Code Style

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

---

## 📦 Creating Your First Module

```bash
# Generate complete module
php artisan kit:make-module Product \
  --with-repository \
  --with-service \
  --with-dto \
  --with-policy \
  --with-filament

# This creates:
# - Domain/Repositories/ProductRepositoryInterface.php
# - Infrastructure/Repositories/ProductRepository.php
# - Domain/Services/ProductServiceInterface.php
# - Application/Services/ProductService.php
# - Application/DTO/ProductInputDTO.php
# - Application/DTO/ProductOutputDTO.php
# - Application/Policies/ProductPolicy.php
# - Filament/Resources/ProductResource.php
```

### Register Bindings

Edit `app/Providers/DomainServiceProvider.php`:

```php
public function register(): void
{
    $this->app->bind(
        \App\Domain\Repositories\ProductRepositoryInterface::class,
        \App\Infrastructure\Repositories\ProductRepository::class
    );

    $this->app->bind(
        \App\Domain\Services\ProductServiceInterface::class,
        \App\Application\Services\ProductService::class
    );
}
```

### Generate Model & Migration

```bash
# Create model with migration
php artisan make:model Product -m

# Or use Blueprint for quick scaffolding
# Create draft.yaml then run:
php artisan blueprint:build
```

---

## 🔧 Common Commands

```bash
# Development (concurrent: server, queue, logs, vite)
composer run dev

# Clear caches
php artisan optimize:clear

# Cache config (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate IDE helper (optional)
# composer require --dev barryvdh/laravel-ide-helper
# php artisan ide-helper:generate

# Queue worker
php artisan queue:work

# Schedule runner
php artisan schedule:run
```

---

## 🚢 Production Deployment

### 1. Environment

```bash
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use strong APP_KEY
php artisan key:generate --force
```

### 2. Optimize

```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev

# Build assets
npm run build

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# Link storage
php artisan storage:link
```

### 3. Permissions

```bash
# Set correct permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Web Server

**Nginx Example:**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/laravel-starter-kit-ptsi/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Supervisor (Queue Worker)

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel-starter-kit-ptsi/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/laravel-starter-kit-ptsi/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 🔒 Security Checklist

- [ ] Change default admin password
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong `APP_KEY`
- [ ] Configure CORS properly
- [ ] Enable HTTPS
- [ ] Set up firewall rules
- [ ] Regular backups
- [ ] Update dependencies regularly
- [ ] Monitor logs
- [ ] Set up rate limiting

---

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Filament Documentation](https://filamentphp.com/docs/4.x)
- [Architecture Guide](./ARCHITECTURE.md)
- [Features List](./FEATURES.md)
- [Contributing Guide](./CONTRIBUTING.md)

---

## 🆘 Troubleshooting

### Common Issues

**1. Migration Error: Table already exists**

```bash
php artisan migrate:fresh --seed
```

**2. Permission Denied on storage/**

```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

**3. 500 Error After Deploy**

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

**4. Filament Assets Not Loading**

```bash
php artisan filament:upgrade
php artisan optimize:clear
npm run build
```

**5. Queue Not Working**

```bash
# Check queue connection in .env
QUEUE_CONNECTION=database

# Run queue worker
php artisan queue:work

# Or use supervisor in production
```

---

## 📞 Support

Need help? Contact PTSI Digital Team:

- 📧 Email: ti@ptsi.co.id
- 🐛 Issues: [GitHub Issues](https://github.com/ptsi-digital/laravel-starter-kit-ptsi/issues)
- 📖 Docs: [Project Wiki](https://github.com/ptsi-digital/laravel-starter-kit-ptsi/wiki)

---

**Last Updated**: November 6, 2025  
**Version**: 1.0.0

