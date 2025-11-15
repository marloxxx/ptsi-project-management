# Laravel Starter Kit — PTSI Edition

> **Laravel 12 + Filament 4 Starter Kit** untuk PT Surveyor Indonesia (PTSI)  
> Based on [Kaido Kit](https://github.com/siubie/kaido-kit) with clean architecture & latest versions  
> Arsitektur **Clean Architecture** dengan Interface-First Pattern

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-4.x-orange.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)

---

## 📋 Fitur Utama

### 🎨 **PTSI Branding & UX**
- ✅ Brand colors resmi (Dark Blue, Sky Blue, Tosca Green)
- ✅ Dark mode + custom Filament theme
- ✅ Bahasa Indonesia-ready dan aksesibilitas default

### 🏗️ **Clean Architecture**
- ✅ Contracts Layer (Interfaces & Contracts)
- ✅ Application Layer (Services, Policies, Actions)
- ✅ Infrastructure Layer (Repositories & integrasi eksternal)
- ✅ Interface-First Pattern & transactional service

### 🔐 **Security & RBAC**
- ✅ Role-Based Access Control (Spatie Permission + Filament Shield)
- ✅ Multi-Factor Authentication (Breezy)
- ✅ User Profile & impersonation tools
- ✅ Activity Logging / audit trail (Spatie Activity Log)

### 📦 **Project Operations**
- ✅ Ticket lifecycle lengkap (status, prioritas, histori, komentar)
- ✅ Board & Timeline view memakai Filament Tab Layout Plugin
- ✅ Epics overview page khusus untuk memantau inisiatif lintas proyek
- ✅ Analytics dashboard (stats overview, trends, assignments)
- ✅ External client portal (login token + dashboard publik)
- ✅ Notifikasi email + database untuk komentar & anggota proyek

### 🛠️ **Developer Experience**
- ✅ Module generator (`kit:make-module`) & scaffolding clean architecture
- ✅ `composer run dev` untuk menjalankan server + queue + Vite + log sekaligus
- ✅ Blueprint, Pint, PHPStan, Pest, Debugbar termasuk di default toolchain
- ✅ Queue worker siap pakai (database/redis) dengan contoh Supervisor

> ⚠️ **Ingat:** Jalankan queue worker (via `composer run dev` atau `php artisan queue:work`) agar notifikasi & dashboard tetap realtime.

---

## 🧩 Modul & Timeline Implementasi

| Phase | Modul / Area                                       | Status |
| ----- | -------------------------------------------------- | :----: |
| 0     | Foundation, branding, env baseline                 | ✅ |
| 1     | Core domain (projects, tickets, priorities)        | ✅ |
| 2     | User & access management (RBAC, MFA, impersonasi)  | ✅ |
| 3     | Project & epic management + notes                  | ✅ |
| 4     | Ticket lifecycle (history, komentar, import/export)| ✅ |
| 5     | Kanban board & timeline (Gantt)                    | ✅ |
| 6     | Analytics dashboard (stats, charts, activity)      | ✅ |
| 7     | Notifications & external client portal             | ✅ |
| 8     | Documentation & developer experience (berjalan)    | 🚧 |

---

## 🚀 Installation

### Prerequisites

- PHP ≥ 8.3
- Composer ≥ 2.6
- Node.js ≥ 20
- MySQL 8 / MariaDB 10.6+
- Git

> **New to this project?** Start with [📖 Documentation Index](./docs/INDEX.md) or [⚡ Quick Start Guide](./docs/QUICK_START.md)

### Installation Steps

```bash
# 1. Clone repository
git clone https://github.com/ptsi-digital/laravel-starter-kit-ptsi.git
cd laravel-starter-kit-ptsi

# 2. Install dependencies
composer install
npm install

# 3. Environment setup
cp .env.example .env
php artisan key:generate
php artisan storage:link

# 4. Configure database (.env)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_starter_ptsi
DB_USERNAME=root
DB_PASSWORD=

# 5. Run migrations & seeders
php artisan migrate --seed

# 6. Build assets
npm run build

# 7. Start development stack
composer run dev  # server + queue + Vite + log stream
# or run them separately:
# php artisan serve
# php artisan queue:work --queue=default --sleep=3 --tries=3
# npm run dev
```

Akses admin panel di: **http://localhost:8000/admin**

### Default Credentials

```
Email: admin@ptsi.co.id
Password: password
```

---

## 🎨 Brand Colors PTSI

```php
'primary'   => '#184980', // Dark Blue PTSI
'secondary' => '#09A8E1', // Sky Blue
'accent'    => '#00B0A8', // Tosca Green
'warning'   => '#FF8939', // Orange
'success'   => '#16A34A',
'danger'    => '#E11D48',
```

---

## 🏗️ Architecture

```
app/
├── Application/          # Use Cases Layer
│   ├── Actions/         # Single-purpose actions
│   ├── Policies/        # Authorization policies
│   └── Services/        # Service implementations
├── Domain/              # Contracts & Interfaces
│   ├── Repositories/    # Repository interfaces
│   └── Services/        # Service interfaces
├── Filament/            # UI Layer (Filament Resources)
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
└── Infrastructure/       # External Concerns
    ├── Repositories/    # Repository implementations
    └── Services/        # External service integrations
```

---

## 🛠️ Module Generator

Generate complete modules with clean architecture:

```bash
php artisan kit:make-module Ticket \
  --with-repository \
  --with-service \
  --with-policy \
  --with-filament
```

### Generated Structure

```
✓ Domain/Repositories/TicketRepositoryInterface.php
✓ Infrastructure/Repositories/TicketRepository.php
✓ Domain/Services/TicketServiceInterface.php
✓ Application/Services/TicketService.php
✓ Application/Policies/TicketPolicy.php
✓ Filament/Resources/TicketResource.php
```

### Register Bindings

Edit `app/Providers/DomainServiceProvider.php`:

```php
public function register(): void
{
    // Repository binding
    $this->app->bind(
        \App\Domain\Repositories\TicketRepositoryInterface::class,
        \App\Infrastructure\Repositories\TicketRepository::class
    );

    // Service binding
    $this->app->bind(
        \App\Domain\Services\TicketServiceInterface::class,
        \App\Application\Services\TicketService::class
    );
}
```

---

## 🔐 Security Setup

Authorization is powered by policies and `spatie/laravel-permission`. Default roles and permissions are seeded during installation, and you can manage them directly from the Filament admin panel.

### Enable MFA (Optional)

MFA sudah enabled di `AdminPanelProvider.php`. User dapat mengaktifkan di **Profile → Two Factor Authentication**.

---

## 📝 Usage Examples

### Example: Service with Transaction

```php
use Illuminate\Support\Facades\DB;

public function create(array $data): Ticket
{
    return DB::transaction(function () use ($data) {
        // Transactional business logic
        $ticket = $this->repository->create($data);
        
        // Log activity
        activity()
            ->performedOn($ticket)
            ->log('Ticket created');
        
        return $ticket;
    });
}
```

### Example: Filament Action with Transaction

```php
use Filament\Actions\Action;

Action::make('approve')
    ->requiresConfirmation()
    ->databaseTransaction()
    ->action(function ($record) {
        app(TicketServiceInterface::class)->update(
            $record->id,
            ['status' => 'approved']
        );
    });
```

---

## 🌐 External Client Portal & Notifications

- **Portal login**: `https://{app}/external/{token}` — token didapat dari halaman Project → External Access.
- **Dashboard**: menampilkan progress proyek, filter status/prioritas, histori aktivitas, dan KPI mingguan.
- **Autentikasi**: password token di-hash, update akses tercatat di `external_access_tokens`.
- **Notifikasi**: komentar tiket & perubahan anggota proyek mengirim email + notifikasi in-app (queued).

### Menjalankan Queue Worker

```bash
# development (recommended)
composer run dev

# production supervisor command
php artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=120
```

Panduan lengkap ada di [`docs/DEPLOYMENT_GUIDE.md`](./docs/DEPLOYMENT_GUIDE.md#-queue-workers-ptsi-ops).

---

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run with coverage
php artisan test --coverage

# Lint code
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

---

## 🚢 Deployment

### Production Setup

```bash
# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# Build assets
npm run build
```

### Using Laravel Octane (Optional)

```bash
composer require laravel/octane
php artisan octane:install --server=swoole
php artisan octane:start
```

---

## 📚 Commands Cheatsheet

```bash
# Development server (with queue, logs, vite)
composer run dev

# Generate module
php artisan kit:make-module {Name} [options]

# Migrations
php artisan migrate --seed
php artisan migrate:fresh --seed

# Cache
php artisan optimize
php artisan optimize:clear

# Testing
php artisan test
./vendor/bin/pint --test
```

---

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📦 Stack

- **Framework:** Laravel 12
- **Admin Panel:** Filament 4
- **Frontend:** Livewire 3, Tailwind CSS v4, Alpine.js
- **Database:** MySQL 8 / MariaDB
- **Cache:** Redis (optional)
- **Queue:** Database (default), Redis (optional)

### Key Packages

- `jeffgreco13/filament-breezy` - User Profile & MFA
- `spatie/laravel-permission` - Permissions
- `spatie/laravel-activitylog` - Activity Logging
- `spatie/laravel-medialibrary` - Media Management
- `spatie/laravel-settings` - Settings Management
- `pxlrbt/filament-excel` - Excel Export/Import

---

## 📄 License

MIT © PT Surveyor Indonesia — Divisi Teknologi Informasi

---

## 🆘 Support

- 📧 Email: ti@ptsi.co.id
- 📚 Documentation: [docs/INDEX.md](./docs/INDEX.md)
- 🐛 Issues: [GitHub Issues](https://github.com/ptsi-digital/laravel-starter-kit-ptsi/issues)

### Additional Features  
- 📖 **API Documentation**: Auto-generated with Scramble at `/docs/api`
- 🐛 **Debug Bar**: Laravel Debugbar for development
- 🏗️ **Blueprint**: Quick model generation via Laravel Shift Blueprint
- 🚀 **Laravel Boost**: AI-powered development assistant with Laravel-specific context and tools
- 📝 **Comprehensive Documentation**: 
  - 📑 **[Documentation Index](./docs/INDEX.md)** - Complete documentation hub
  - ⚡ **[Quick Start](./docs/QUICK_START.md)** - 5-minute setup guide
  - 🔥 **[Laravel Boost Guidelines](./docs/LARAVEL_BOOST_GUIDE.md)** - **MUST READ** coding standards
  - 🏛️ [Architecture Guide](./ARCHITECTURE.md) - Clean architecture patterns
  - 🛠️ [Setup Guide](./SETUP.md) - Installation & configuration
  - 📋 [Features Checklist](./FEATURES.md) - Complete features list
  - 📝 [Changelog](./CHANGELOG.md) - Version history

---

## 🙏 Credits

- **Laravel:** Taylor Otwell & Laravel Team
- **Filament:** Dan Harrin & Filament Team
- **PTSI Digital Team**

---

**Built with ❤️ for PT Surveyor Indonesia**
