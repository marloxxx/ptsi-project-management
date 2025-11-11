# 🔒 Security & Compliance Guide — PTSI Laravel Boost Standard

## 🎯 Tujuan

Menetapkan kebijakan keamanan aplikasi Laravel Boost internal PTSI agar setiap proyek:

* Aman dari serangan umum (OWASP Top 10).
* Memiliki audit trail yang lengkap.
* Mendukung autentikasi berlapis (MFA, Token, Role).
* Sesuai prinsip **ISO/IEC 27001** dan kebijakan **IT Security PTSI Digital**.

---

## 🧩 1. Pilar Keamanan PTSI

| Pilar               | Implementasi Laravel              |
| ------------------- | --------------------------------- |
| **Confidentiality** | Encryption (AES-256 / TLS 1.3)    |
| **Integrity**       | Hashing (Argon2id), signed tokens |
| **Availability**    | Octane, Horizon, failover DB      |
| **Auditability**    | Activity Log, DB audit trail      |
| **Accountability**  | MFA + Role-based access           |
| **Non-repudiation** | Signed transaction logs           |

---

## 🧱 2. Authentication & MFA

### 2.1. Laravel Fortify / Breeze + MFA

Gunakan **Laravel Fortify** atau **Filament Breezy** untuk login & MFA:

```bash
composer require filament/breezy
php artisan breezy:install --two-factor
```

Aktifkan TOTP (Google Authenticator) & fallback email OTP.

### 2.2. Session Security

* `SESSION_SECURE_COOKIE=true`
* `SESSION_SAME_SITE=strict`
* `SESSION_DRIVER=database`
* `SESSION_LIFETIME=30`

### 2.3. Rate Limiting

Gunakan throttle pada `LoginController`:

```php
RateLimiter::for('login', fn(Request $r) =>
  Limit::perMinute(5)->by($r->ip())
);
```

---

## 🧾 3. Role-Based Access Control (RBAC)

Gunakan **spatie/laravel-permission**.

### Contoh seeder:

```php
Role::create(['name' => 'admin']);
Role::create(['name' => 'manager']);
Role::create(['name' => 'viewer']);

Permission::create(['name' => 'user.manage']);

$admin->givePermissionTo('user.manage');
```

Tambahkan `Gate` di Policy:

```php
public function update(User $user, Model $target): bool {
    return $user->hasPermissionTo('user.manage');
}
```

---

## 🔐 4. Data Protection & Encryption

| Jenis Data        | Proteksi                 |
| ----------------- | ------------------------ |
| Password          | Hash = Argon2id          |
| Token / JWT       | RSA SHA-256              |
| Data Sensitif     | `Crypt::encryptString()` |
| Storage / File    | Disk-level encryption    |
| DB Connection     | TLS 1.2+ / SSL_CA        |
| API Communication | HTTPS + Signature        |

---

## 🧾 5. Token & JWT Standard

Gunakan **Laravel Sanctum** untuk API internal:

```bash
composer require laravel/sanctum
php artisan sanctum:install
```

Atur expiry:

```php
'guard' => 'sanctum',
'expiration' => 60, // 60 menit
```

Untuk SSO/eksternal → gunakan JWT (RSA SHA256).
Tambahkan `nonce` & `aud` untuk anti-replay.

---

## 📜 6. Logging & Audit Trail

Gunakan **spatie/laravel-activitylog**:

```php
activity()
  ->causedBy(auth()->user())
  ->performedOn($model)
  ->withProperties(['changes' => $model->getChanges()])
  ->log('updated profile');
```

Aktifkan audit pada semua model penting:

```php
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model {
  use LogsActivity;
  
  protected static $logAttributes = ['*'];
  protected static $logOnlyDirty = true;
}
```

---

## 🧠 7. Integrity & Tamper Detection

### 7.1. Signed Payload

Gunakan helper `hash_hmac()` untuk validasi data penting:

```php
$signature = hash_hmac('sha256', $payload, env('APP_KEY'));
```

### 7.2. File Upload Hash

Saat file disimpan, generate SHA256 checksum:

```php
$fileHash = hash_file('sha256', $path);
```

---

## 🔍 8. Input Validation & Sanitization

Gunakan **FormRequest**:

```php
class StoreUserRequest extends FormRequest {
  public function rules(): array {
    return [
      'email' => 'required|email:rfc,dns',
      'name' => 'required|string|max:100',
    ];
  }
}
```

> Hindari query langsung. Gunakan Repository + parameter binding.

---

## ⚙️ 9. Transport Layer Security

* Semua domain wajib HTTPS (TLS 1.3)
* Redirect HTTP → HTTPS di Nginx
* Gunakan HSTS:

```
add_header Strict-Transport-Security "max-age=31536000" always;
```

* Nonaktifkan insecure cipher suites.

---

## 🧾 10. Database Security

| Area       | Kebijakan                                     |
| ---------- | --------------------------------------------- |
| Privilege  | Gunakan user DB terpisah per environment      |
| Connection | SSL wajib (`MYSQL_ATTR_SSL_CA`)               |
| Query      | Eloquent / Query Builder, tidak raw SQL       |
| Backup     | Enkripsi + kompresi                           |
| Audit      | Log SQL critical query (Insert/Update/Delete) |

---

## 🧰 11. File & Storage Security

* Simpan semua upload di `storage/app/private`
* Gunakan `Storage::get()` + Signed URL untuk akses:

```php
$url = Storage::temporaryUrl($path, now()->addMinutes(5));
```

* Enkripsi metadata (`encrypt` / `decrypt`).

---

## 🪙 12. Compliance & Audit Readiness

| Area                 | Requirement           | Implementasi                |
| -------------------- | --------------------- | --------------------------- |
| **ISO 27001 A.9.1**  | Access Control Policy | RBAC + MFA                  |
| **ISO 27001 A.12.4** | Logging & Monitoring  | ActivityLog + Horizon       |
| **ISO 27001 A.14.1** | Secure Development    | Review + CI/CD check        |
| **ISO 27001 A.18.1** | Legal Compliance      | Encrypted PII storage       |
| **GDPR Art. 32**     | Data Protection       | Encryption + opt-in consent |

---

## ⚡ 13. CI/CD Security Hooks

Sebelum deploy:

* Jalankan **Laravel Pint**, **PHPStan**, **Pest**.
* Validasi `.env` berisi:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.ptsi.co.id
```

* Tambahkan ke pipeline:

```yaml
- name: Security Audit
  run: ./vendor/bin/laravel-security-checker
```

---

## 🔒 14. Environment & Secret Management

| Komponen     | Tools                               |
| ------------ | ----------------------------------- |
| Secret Store | GitHub Actions Secrets / .env Vault |
| Rotation     | 90 hari (password/token)            |
| Vault Backup | Enkripsi AES-256                    |
| Access       | Minimal Privilege Only              |

---

## 🧠 15. Anti-Tampering & Non-Repudiation

Gunakan `signed` token untuk aksi kritikal:

```php
URL::signedRoute('approve', ['id' => $id]);
```

Tambahkan column `signature` pada tabel sensitif, berisi hash payload lengkap:

```php
$record->signature = hash_hmac('sha256', json_encode($record->toArray()), env('APP_KEY'));
```

---

## 🧾 16. Periodic Security Checklist

| Frekuensi | Aktivitas                        |
| --------- | -------------------------------- |
| Harian    | Audit log, queue Horizon         |
| Mingguan  | Review permission, failed login  |
| Bulanan   | Backup restore test              |
| Triwulan  | Security scan & dependency check |
| Tahunan   | Penetration test internal        |

---

## 🧩 17. Incident Response Flow

1. 🔔 Deteksi anomali (log alert / uptime monitor)
2. 🕵🏻‍♀️ Analisis sumber (IP, payload, scope)
3. 🛑 Isolasi sistem & revoke token
4. 🧮 Restore dari backup terbaru
5. 🧾 Buat laporan insiden ke Div. TI PTSI
6. 🔐 Perbaiki & patch

---

## 📜 18. Kesimpulan

Dokumen ini menetapkan fondasi keamanan untuk seluruh sistem Laravel Boost PTSI:

✅ Aman · ✅ Audit-ready · ✅ Patuh ISO 27001 · ✅ Siap untuk scale

---

📁 **File:** `docs/SECURITY_COMPLIANCE_GUIDE.md`
📅 **Versi:** 1.0.0
✍️ **Author:** Divisi Teknologi Informasi – PT Surveyor Indonesia
📜 **Lisensi:** MIT License © 2025

