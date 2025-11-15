# ⚡ Quick Start Guide

## 🎯 5-Minute Setup

### 1. Clone & Install (2 min)

```bash
git clone https://github.com/ptsi-digital/laravel-starter-kit-ptsi.git
cd laravel-starter-kit-ptsi
composer install
npm install
```

### 2. Configure (1 min)

```bash
cp .env.example .env
php artisan key:generate

# Edit .env:
# DB_DATABASE=laravel_starter_ptsi
# DB_USERNAME=root
# DB_PASSWORD=your_password
```

### 3. Database Setup (1 min)

```bash
php artisan migrate --seed
php artisan shield:generate --all
php artisan shield:super-admin
# Select: admin@ptsi.co.id
```

### 4. Run (1 min)

```bash
# Option 1: Simple
composer run dev  # server + queue + vite + log stream

# Option 2: Manual
php artisan serve
php artisan queue:work --queue=default --sleep=3 --tries=3
npm run dev
```

Visit: **http://localhost:8000/admin**

**Login:**
- Email: `admin@ptsi.co.id`
- Password: `password`

---

## 🚀 Create Your First Module (3 min)

```bash
# 1. Generate module (30 sec)
php artisan kit:make-module Product \
  --with-repository \
  --with-service \
  --with-dto \
  --with-policy \
  --with-filament

# 2. Create model (30 sec)
php artisan make:model Product -m

# 3. Edit migration (1 min)
# database/migrations/xxxx_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

# 4. Register bindings (30 sec)
# app/Providers/DomainServiceProvider.php
$this->app->bind(
    \App\Domain\Repositories\ProductRepositoryInterface::class,
    \App\Infrastructure\Repositories\ProductRepository::class
);
$this->app->bind(
    \App\Domain\Services\ProductServiceInterface::class,
    \App\Application\Services\ProductService::class
);

# 5. Migrate & generate permissions (30 sec)
php artisan migrate
php artisan shield:generate --all
```

**Done!** Your module is ready. Now edit:
- `app/Filament/Resources/ProductResource.php` for UI
- `app/Infrastructure/Repositories/ProductRepository.php` for queries
- `app/Application/Services/ProductService.php` for business logic

---

## 📁 What You Got

### Core Features
- ✅ Laravel 12 + Filament 4
- ✅ RBAC (Roles & Permissions)
- ✅ MFA (Two-Factor Authentication)
- ✅ User Impersonation
- ✅ Activity Logging
- ✅ Media Library
- ✅ Excel Import/Export
- ✅ Kanban Board & Timeline (Gantt)
- ✅ Analytics Dashboard (stats, charts, user load)
- ✅ External Client Portal (token-based access)
- ✅ API Documentation & Swagger via Scramble

### Developer Tools
- ✅ Laravel Boost (AI Assistant)
- ✅ Module Generator
- ✅ Debugbar
- ✅ Blueprint
- ✅ Pint (Code Formatter)
- ✅ Docker (Sail)

### Architecture
- ✅ Clean Architecture
- ✅ Repository Pattern
- ✅ Service Pattern
- ✅ DTO Pattern
- ✅ Policy-based Authorization
- ✅ Queue-first notifications

---

## 🎨 PTSI Branding

Already configured with PTSI colors:

```php
'primary'   => '#184980', // Dark Blue
'secondary' => '#09A8E1', // Sky Blue
'accent'    => '#00B0A8', // Tosca Green
'warning'   => '#FF8939', // Orange
```

Dark mode enabled by default ✨

---

## 📚 Next Steps

1. **Read Documentation**
   - [README.md](../README.md) - Full overview
   - [ARCHITECTURE.md](../ARCHITECTURE.md) - Architecture guide
   - [LARAVEL_BOOST_GUIDE.md](./LARAVEL_BOOST_GUIDE.md) - Coding standards

2. **Create Your First Module**
   - Follow "Create Your First Module" section above

3. **Customize**
   - Update `config/filament-shield.php` for permissions
   - Update `config/app.php` for app settings
   - Add your business modules dan queue worker

4. **Deploy**
   - Read [SETUP.md](../SETUP.md) for production deployment
   - Siapkan Supervisor (lihat [Deployment Guide](./DEPLOYMENT_GUIDE.md))

---

## 🆘 Quick Troubleshooting

**Can't login?**
```bash
php artisan migrate:fresh --seed
php artisan shield:super-admin
```

**Assets not loading?**
```bash
php artisan filament:upgrade
npm run build
```

**Permission errors?**
```bash
sudo chmod -R 775 storage bootstrap/cache
```

**Need help?**
- 📧 ti@ptsi.co.id
- 🐛 GitHub Issues

---

**Happy Coding! 🚀**

