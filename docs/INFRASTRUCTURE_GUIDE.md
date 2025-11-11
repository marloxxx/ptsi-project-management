# 🧱 Infrastructure & Server Provisioning Guide — PTSI Laravel Boost Standard

## 🎯 Tujuan

Menetapkan standar instalasi, konfigurasi, dan pengamanan server untuk semua proyek Laravel internal PTSI, agar:

* Lingkungan server seragam dan siap untuk CI/CD.
* Performanya optimal (Octane-ready).
* Aman dan mudah di-maintain.
* Mendukung rollback & recovery cepat.

---

## 🧩 1. Rekomendasi OS & Stack

| Komponen                 | Versi Rekomendasi                          |
| ------------------------ | ------------------------------------------ |
| **OS**                   | Ubuntu 24.04 LTS (Minimal Install)         |
| **Web Server**           | Nginx 1.24+                                |
| **PHP**                  | 8.3 (FPM + Opcache)                        |
| **Database**             | MySQL 8 / MariaDB 10.11                    |
| **Cache / Queue**        | Redis 7                                    |
| **Supervisor / Horizon** | Laravel Horizon (preferred)                |
| **SSL**                  | Let's Encrypt / Wildcard PTSI              |
| **Runtime**              | Octane (Swoole)                            |
| **Monitoring**           | Uptime Kuma / Laravel Telescope (internal) |

---

## ⚙️ 2. Initial Server Setup (Ubuntu 24.04)

### 2.1. System Preparation

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install nginx php8.3-fpm php8.3-cli php8.3-bcmath php8.3-mbstring \
php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-mysql redis-server git unzip ufw -y
```

### 2.2. Add Deploy User

```bash
adduser deploy
usermod -aG sudo deploy
su - deploy
ssh-keygen -t ed25519
```

Tambahkan public key ke `/root/.ssh/authorized_keys`.

---

## 🔒 3. Security Hardening

### Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

### Disable Root SSH

Edit `/etc/ssh/sshd_config`

```
PermitRootLogin no
PasswordAuthentication no
```

Kemudian:

```bash
sudo systemctl restart ssh
```

### Fail2Ban

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
```

---

## 🧾 4. Directory Structure (Standard)

```
/var/www/ptsi-app/
 ├── current -> releases/20251107_1300/
 ├── releases/
 │    ├── 20251106_1200/
 │    ├── 20251107_1300/
 ├── shared/
 │    ├── .env
 │    ├── storage/
 │    ├── logs/
```

> Semua proses deploy CI/CD akan menulis ke `releases/`, lalu symbolic link `current` diarahkan ke versi terbaru.

---

## 🌐 5. Nginx Configuration

`/etc/nginx/sites-available/ptsi-app.conf`

```nginx
server {
    listen 80;
    server_name app.ptsi.co.id;
    root /var/www/ptsi-app/current/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    access_log /var/log/nginx/ptsi-access.log;
    error_log /var/log/nginx/ptsi-error.log;

    client_max_body_size 100M;
}

server {
    listen 443 ssl;
    server_name app.ptsi.co.id;

    ssl_certificate /etc/letsencrypt/live/app.ptsi.co.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.ptsi.co.id/privkey.pem;
    include snippets/ssl-params.conf;

    return 301 https://$host$request_uri;
}
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/ptsi-app.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 🪙 6. PHP-FPM Optimization

`/etc/php/8.3/fpm/php.ini`

```
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 120
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
```

Restart:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 🧰 7. Supervisor & Queue Configuration

**Gunakan Horizon (recommended):**

```bash
php artisan horizon:install
php artisan horizon
```

Atau Supervisor manual:

`/etc/supervisor/conf.d/laravel-worker.conf`

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ptsi-app/current/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/ptsi-app/shared/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## ⚡ 8. Octane Setup (Swoole)

```bash
composer require laravel/octane --dev
php artisan octane:install
```

Systemd service:

`/etc/systemd/system/octane.service`

```ini
[Unit]
Description=Laravel Octane (Swoole)
After=network.target

[Service]
User=deploy
WorkingDirectory=/var/www/ptsi-app/current
ExecStart=/usr/bin/php artisan octane:start --server=swoole --port=8000
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable service:

```bash
sudo systemctl enable octane
sudo systemctl start octane
```

---

## 🧠 9. Health Check

Tambahkan endpoint untuk monitoring otomatis:

```php
Route::get('/health', fn() => response()->json([
    'app' => config('app.name'),
    'status' => 'OK',
    'time' => now(),
]));
```

Gunakan monitoring eksternal (Opsgenie / Uptime Kuma) dengan interval 60 detik.

---

## 📦 10. Auto Backup Strategy

Gunakan `spatie/laravel-backup`:

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan backup:run
```

Buat cron job:

```bash
0 3 * * * cd /var/www/ptsi-app/current && php artisan backup:run >> /var/log/laravel-backup.log 2>&1
```

Kirim hasil backup ke:

* **S3 Glacier (Offsite)**
* **Local compressed tar** di `/var/backups/ptsi-app/`

---

## 🔍 11. Monitoring & Logging

| Tool                      | Tujuan               |
| ------------------------- | -------------------- |
| **Laravel Telescope**     | Request log internal |
| **Spatie Activity Log**   | Jejak perubahan data |
| **Horizon Dashboard**     | Monitor job queue    |
| **Uptime Kuma / Grafana** | Server uptime        |
| **Fail2Ban log**          | Keamanan login       |

---

## 🧾 12. Disaster Recovery (Rollback Manual)

Langkah rollback cepat:

```bash
cd /var/www/ptsi-app
ls -lt releases/
ln -sfn releases/20251106_1200 current
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

Restore dari backup:

```bash
php artisan backup:restore --from=latest
```

---

## 🔐 13. Server Audit Checklist

| Area       | Harus Dicek              |
| ---------- | ------------------------ |
| SSH Key    | ✅ Non-root, no password  |
| Firewall   | ✅ Port 22, 80, 443 only  |
| SSL        | ✅ HTTPS aktif            |
| Cron Jobs  | ✅ Backup & optimize      |
| Supervisor | ✅ Worker aktif           |
| Horizon    | ✅ Queue monitoring jalan |
| Logs       | ✅ Rotasi 7 hari          |

---

## 🧱 14. Infrastructure-as-Code (Opsional)

Gunakan **Ansible / Terraform** untuk provisioning otomatis:

```yaml
- name: Deploy PTSI Laravel Boost Server
  hosts: ptsi
  become: yes
  tasks:
    - name: Update system
      apt: update_cache=yes upgrade=dist
    
    - name: Install stack
      apt: name={{ item }} state=present
      loop:
        - nginx
        - php8.3-fpm
        - redis-server
        - git
```

---

## 📜 15. Penutup

> Semua server produksi PTSI **harus mengikuti panduan ini** sebelum menerima deploy pertama.

> Dokumen ini memastikan seluruh sistem Laravel Boost berjalan:

> ✅ Cepat · ✅ Aman · ✅ Dapat diaudit · ✅ Siap di-scale

---

📁 **File:** `docs/INFRASTRUCTURE_GUIDE.md`
📅 **Versi:** 1.0.0
✍️ **Author:** Divisi Teknologi Informasi – PT Surveyor Indonesia
📜 **Lisensi:** MIT License © 2025

