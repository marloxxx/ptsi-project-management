# 🚀 CI/CD Deployment Guide — PTSI Laravel Boost Standard

## 🎯 Tujuan

Menjamin semua proyek Laravel internal PTSI:

* Menggunakan **pipeline CI/CD yang aman dan konsisten.**
* Dapat **deploy otomatis** (via GitHub Actions / aapanel / Docker).
* Memiliki **strategi rollback dan atomic release**.
* Siap untuk **Laravel Octane** (Swoole/RoadRunner).

---

## 🧩 1. Struktur Branch

| Branch      | Environment | Deploy Target                | Status       |
| ----------- | ----------- | ---------------------------- | ------------ |
| `main`      | Production  | `https://app.ptsi.co.id`     | 🔒 Protected |
| `develop`   | Staging     | `https://staging.ptsi.co.id` | 🔐 Partial   |
| `feature/*` | Developer   | Manual (Local)               | 🧱           |

> Semua perubahan harus melalui Pull Request → `develop` → `main`.
> **Tidak ada commit langsung ke main.**

---

## ⚙️ 2. CI/CD Overview (GitHub Actions)

### 2.1 Pipeline File: `.github/workflows/deploy.yml`

```yaml
name: Deploy Laravel Boost

on:
  push:
    branches:
      - main
      - develop

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, bcmath, intl, gd, zip, pdo_mysql
      
      - name: Install Dependencies
        run: |
          composer install --no-interaction --prefer-dist --optimize-autoloader
          npm ci && npm run build
      
      - name: Run Tests
        run: |
          ./vendor/bin/pint --test
          ./vendor/bin/phpstan analyse
          ./vendor/bin/pest

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop'
    steps:
      - uses: actions/checkout@v4
      
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          port: 22
          script: |
            cd /var/www/ptsi-app
            git fetch origin
            git reset --hard origin/${GITHUB_REF##*/}
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan optimize:clear
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo systemctl restart nginx
```

---

## 🔑 3. Secrets yang Harus Diset di GitHub

| Nama             | Deskripsi                            |
| ---------------- | ------------------------------------ |
| `SERVER_HOST`    | IP atau domain server                |
| `SERVER_USER`    | Username SSH (mis. `ubuntu`, `root`) |
| `SERVER_SSH_KEY` | Private key (tanpa passphrase)       |
| `APP_ENV`        | `production` atau `staging`          |
| `APP_URL`        | URL environment target               |

---

## 🧱 4. Deploy via aapanel (Manual Pipeline)

1. **Clone repo pertama kali**

```bash
cd /www/wwwroot/
git clone git@github.com:ptsi-digital/ptsi-admin-starter.git
cd ptsi-admin-starter
composer install
cp .env.example .env
php artisan key:generate
npm ci && npm run build
php artisan migrate --seed
```

2. **Setup Nginx (aapanel)**

   * Root path: `/www/wwwroot/ptsi-admin-starter/public`
   * Tambahkan SSL (Let's Encrypt / wildcard)
   * Aktifkan PHP-FPM 8.3

3. **Auto Deploy dengan Webhook GitHub**

   * Tambahkan file `.deploy.sh`:

```bash
#!/bin/bash
cd /www/wwwroot/ptsi-admin-starter || exit
git fetch origin main
git reset --hard origin/main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

   * Beri izin:

```bash
chmod +x .deploy.sh
```

   * Buat webhook GitHub → trigger script `.deploy.sh` via endpoint:

```
https://app.ptsi.co.id/deploy?token=SECRET_KEY
```

---

## 🐳 5. Docker Deployment (Opsional untuk Isolasi)

**Dockerfile**

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]
```

**docker-compose.yml**

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: ptsi_app
    restart: always
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=production
      - APP_URL=https://app.ptsi.co.id
      - DB_HOST=db
      - DB_DATABASE=ptsi
      - DB_USERNAME=root
      - DB_PASSWORD=root

  db:
    image: mysql:8
    container_name: ptsi_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: ptsi
    ports:
      - "3306:3306"

  redis:
    image: redis:latest
    container_name: ptsi_redis
    ports:
      - "6379:6379"
```

---

## 🧱 6. Atomic Release (Zero Downtime)

Gunakan pattern "release directory":

```
/var/www/ptsi-app/
 ├── releases/
 │    ├── 20251106_1300/
 │    ├── 20251107_0900/
 ├── current -> releases/20251107_0900/
 ├── shared/ (storage, .env)
```

**Deploy script ringkas:**

```bash
TIMESTAMP=$(date +"%Y%m%d_%H%M")
RELEASE_DIR="/var/www/ptsi-app/releases/$TIMESTAMP"

mkdir -p $RELEASE_DIR
git clone -b main git@github.com:ptsi-digital/ptsi-admin-starter.git $RELEASE_DIR

cd $RELEASE_DIR
composer install --no-dev --optimize-autoloader

ln -s /var/www/ptsi-app/shared/.env .env
ln -s /var/www/ptsi-app/shared/storage storage

php artisan migrate --force
php artisan optimize

ln -sfn $RELEASE_DIR /var/www/ptsi-app/current
sudo systemctl restart nginx
```

Rollback cukup:

```bash
ln -sfn /var/www/ptsi-app/releases/20251106_1300 /var/www/ptsi-app/current
sudo systemctl restart nginx
```

---

## ⚡ 7. Octane Deployment

### Install

```bash
composer require laravel/octane --dev
php artisan octane:install
```

### Start server

```bash
php artisan octane:start --server=swoole --port=8000
```

### Systemd service (optional)

`/etc/systemd/system/octane.service`

```ini
[Unit]
Description=Laravel Octane
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/ptsi-app/current
ExecStart=/usr/bin/php artisan octane:start --server=swoole --port=8000
Restart=always

[Install]
WantedBy=multi-user.target
```

---

## 🧪 8. Post-Deploy Checklist

| Task                                     | Status |
| ---------------------------------------- | ------ |
| ✅ `.env` sinkron dengan server           |        |
| ✅ `php artisan migrate --force` sukses   |        |
| ✅ `php artisan optimize` dijalankan      |        |
| ✅ Queue worker berjalan (Horizon/Octane) |        |
| ✅ Log & storage permission OK            |        |
| ✅ Health check (`/health`) 200 OK        |        |

---

## 🧭 9. Rollback Quick Command

```bash
cd /var/www/ptsi-app
ls -lt releases/
ln -sfn releases/<timestamp_sebelumnya> current
sudo systemctl restart nginx
```

---

## 🔒 10. Security Notes

* Gunakan **SSH key** (bukan password) untuk semua server.
* **Nonaktifkan root login langsung** di `sshd_config`.
* Gunakan **fail2ban** untuk brute-force protection.
* Batasi permission folder `/storage` dan `/bootstrap/cache`.
* Backup otomatis via cron & offsite S3/Glacier.

---

## 📦 11. Monitoring & Logging

* Gunakan `spatie/laravel-activitylog` untuk jejak user.
* Tambahkan `/health` endpoint:

```php
Route::get('/health', fn() => response()->json(['ok' => true]));
```

* Gunakan `laravel/horizon` untuk monitoring queue.
* Kirim alert ke Slack (opsional) dengan webhook `LOG_CHANNEL=slack`.

---

## 🧾 12. Backup Strategy

Gunakan `spatie/laravel-backup`:

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan backup:run
```

Jadwalkan cron:

```
0 2 * * * cd /var/www/ptsi-app/current && php artisan backup:run >> /var/log/laravel-backup.log 2>&1
```

---

## 📜 13. Kesimpulan

Dengan panduan ini, semua proyek Laravel Boost internal PTSI akan:

* ✅ Menggunakan pipeline CI/CD yang konsisten.
* ✅ Siap untuk **zero downtime** & **rollback cepat**.
* ✅ Aman, terstruktur, dan mudah diaudit.
* ✅ Support untuk **Octane, Docker, dan aapanel**.

---

📁 **File:** `docs/DEPLOYMENT_GUIDE.md`
📅 **Versi:** 1.0.0
✍️ **Author:** Divisi Teknologi Informasi – PT Surveyor Indonesia
📜 **Lisensi:** MIT License © 2025

