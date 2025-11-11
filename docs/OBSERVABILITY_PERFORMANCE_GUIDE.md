# 📗 Observability & Performance Guide — PTSI Laravel Boost Standard

## 🎯 Tujuan

Menetapkan **standar pemantauan, performa, dan efisiensi** untuk semua proyek Laravel Boost internal PTSI agar:

* Setiap sistem **terukur dan mudah dilacak** (traceable).
* Performa tinggi dan **beban server terkontrol**.
* Mendukung observability end-to-end (log → metric → trace).

---

## 🧱 1. Pilar Observability

| Pilar        | Tujuan                       | Implementasi                |
| ------------ | ---------------------------- | --------------------------- |
| **Logging**  | Merekam setiap event penting | Monolog, Activity Log       |
| **Metrics**  | Mengukur performa runtime    | Laravel Horizon, Prometheus |
| **Tracing**  | Melacak alur request dan job | Telescope, OpenTelemetry    |
| **Alerting** | Deteksi dini anomali         | Uptime Kuma, Slack alerts   |

---

## 🧩 2. Logging Standar

Gunakan **Monolog** sebagai handler utama (default Laravel).

### 2.1. Struktur Log Directory

```
storage/logs/
 ├── laravel.log          # Default app log
 ├── query.log            # Query performance
 ├── jobs.log             # Queue process log
 ├── audit.log            # Activity & compliance
```

### 2.2. Format Log

Gunakan JSON untuk mudah di-parse monitoring tools.

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'info',
        'days' => 14,
        'tap' => [App\Logging\CustomizeFormatter::class],
    ],
]
```

**Slack Alert:**

```env
LOG_CHANNEL=stack
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXXX/YYYY/ZZZZ
```

---

## ⚙️ 3. Query & Job Profiling

Gunakan **Laravel Telescope** atau **Clockwork** untuk profiling lokal:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
php artisan serve
```

Di production, matikan auto-record dan simpan ke Redis:

```php
Telescope::filter(function (IncomingEntry $entry) {
    return $entry->isReportableException() || $entry->type === EntryType::Job;
});
```

---

## ⚡ 4. Caching Strategy

| Jenis Data        | Cache Layer    | TTL        | Catatan                    |
| ----------------- | -------------- | ---------- | -------------------------- |
| Master Data       | Redis          | 24 jam     | `Cache::remember()`        |
| Configuration     | Config Cache   | Infinite   | `php artisan config:cache` |
| Dashboard Metrics | Redis          | 5-15 menit | Auto-refresh UI            |
| Query Heavy Table | Database Cache | 60 menit   | per model                  |
| API Response      | HTTP Cache     | 15 menit   | CDN-ready                  |

**Contoh:**

```php
Cache::remember('venues', 60*60, fn() => Venue::all());
```

---

## 🧮 5. Queue Performance & Horizon

### Horizon Setup

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
php artisan horizon
```

### Horizon Configuration

`config/horizon.php`

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'emails', 'jobs'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'minProcesses' => 3,
            'retry' => 3,
        ],
    ],
],
```

Monitoring:

```
/horizon/dashboard
```

Tambahkan alert Slack:

```php
Horizon::routeSlackNotificationsTo(config('services.slack.horizon'));
```

---

## 🚀 6. Octane Optimization

| Komponen         | Rekomendasi                |
| ---------------- | -------------------------- |
| **Server**       | Swoole                     |
| **Worker**       | 8–12 (tergantung core CPU) |
| **Task Workers** | 4–6                        |
| **Static Files** | Served by Nginx            |
| **Hot Reload**   | Disabled in production     |

**Config:**

```bash
php artisan octane:start --server=swoole --workers=8 --max-requests=500
```

Tambahkan pada `.env`:

```
OCTANE_SERVER=swoole
OCTANE_MAX_REQUESTS=500
OCTANE_WORKERS=8
```

---

## 🧾 7. Database Optimization

1. **Gunakan Index**

```sql
CREATE INDEX idx_status ON bookings(status);
```

2. **Gunakan Lazy Loading secara selektif**

```php
Booking::with('venue', 'user')->get();
```

3. **Gunakan Query Caching (Redis)**

```php
$events = Cache::remember('events.all', 300, fn() => Event::all());
```

4. **Gunakan Connection Pool (Octane)**

Tambahkan `DB_CONNECTION_POOL=true` di `.env`.

---

## 🧰 8. Monitoring & Metrics Dashboard

### a) Prometheus Integration

Gunakan package:

```bash
composer require renoki-co/laravel-exporter
```

Akses metrics:

```
/metrics
```

Metrics yang tersedia:

* `laravel_request_count`
* `laravel_request_duration`
* `laravel_jobs_processed_total`
* `laravel_queue_failed_total`

### b) Grafana Dashboard

* Source: Prometheus endpoint `/metrics`
* Panels: request count, queue jobs, CPU usage, response latency

---

## 🧠 9. Performance Benchmark Target

| Area                     | Target       |
| ------------------------ | ------------ |
| **Page Load (TTV)**      | < 1.5s       |
| **API Response Time**    | < 250ms      |
| **Throughput**           | > 100 RPS    |
| **Error Rate**           | < 0.5%       |
| **CPU Usage**            | < 70%        |
| **Memory Usage**         | < 65%        |
| **DB Query per Request** | < 15 queries |

Gunakan `php artisan optimize:report` untuk memantau bottleneck.

---

## 🔎 10. Alerting & Health Check

Tambahkan route health:

```php
Route::get('/health', function () {
  return response()->json(['status' => 'ok', 'uptime' => now()]);
});
```

Monitor dengan:

* **Uptime Kuma** (internal PTSI)
* **Health Check Plugin**
* Slack integration → `#infra-alert`

Contoh alert:

```
:rotating_light: [PTSI Admin] High CPU usage detected (85%)
```

---

## 🧾 11. Asset & Frontend Optimization

| Area          | Strategi                    |
| ------------- | --------------------------- |
| CSS/JS        | Minify + Vite build         |
| Images        | Convert ke WebP             |
| Fonts         | Preload Geomanist           |
| Lazy Load     | Untuk gambar berat          |
| CDN           | Cloudflare / AWS CloudFront |
| Cache-Control | max-age=31536000, immutable |

---

## ⚙️ 12. Scheduler & Maintenance Jobs

Gunakan command scheduler:

```bash
* * * * * cd /var/www/ptsi-app/current && php artisan schedule:run >> /dev/null 2>&1
```

Gunakan log rotation:

```
logrotate /var/log/nginx/*.log {
  weekly
  rotate 4
  compress
  missingok
  notifempty
}
```

---

## 🧩 13. Tracing & Request Flow

Gunakan **OpenTelemetry** untuk distributed tracing:

```bash
composer require open-telemetry/opentelemetry
```

Integrasi dengan Jaeger atau Tempo untuk memvisualisasi alur request:

* TraceID → Request → Job → Notification
* Gunakan correlation ID (`X-Request-ID`) untuk setiap request inbound.

---

## 🧠 14. Best Practice Performance Summary

| Area                     | Rekomendasi                      |
| ------------------------ | -------------------------------- |
| **Cache Everything**     | Redis + config cache             |
| **DB Pooling**           | Octane + persistent connection   |
| **Reduce Query Count**   | eager load, no N+1               |
| **Parallel Queue**       | Horizon balance auto             |
| **Static Asset Offload** | CDN                              |
| **Log Rotation**         | max 14 days                      |
| **Alert Rules**          | Slack notif on threshold breach  |
| **Observability Stack**  | Telescope + Prometheus + Grafana |

---

## 📜 15. Kesimpulan

Dengan mengikuti panduan observability & performance ini, sistem Laravel Boost PTSI akan:

* **Selalu terpantau (observable)**
* **Cepat dan efisien (optimized)**
* **Mudah didiagnosa dan diperbaiki (traceable)**
* **Siap untuk beban tinggi (Octane-ready)**

---

📁 **File:** `docs/OBSERVABILITY_PERFORMANCE_GUIDE.md`
📅 **Versi:** 1.0.0
✍️ **Author:** Divisi Teknologi Informasi – PT Surveyor Indonesia
📜 **Lisensi:** MIT License © 2025

