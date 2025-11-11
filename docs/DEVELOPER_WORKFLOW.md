# 🧭 Developer Workflow — PTSI Laravel Boost Standard

## 🎯 Tujuan

Menetapkan **standar kerja pengembangan** proyek berbasis **Laravel Boost (Clean Architecture)** agar:

* Setiap kontributor mengikuti pola yang **konsisten dan aman**.
* Proses review, testing, dan release **terkendali**.
* CI/CD pipeline berjalan **tanpa konflik atau duplikasi logic**.

---

## 🧩 1. Branching Strategy

Gunakan pola **Gitflow Simplified**, hanya tiga branch utama:

| Branch      | Tujuan                          | Diperbarui oleh         |
| ----------- | ------------------------------- | ----------------------- |
| `main`      | Stable release production       | Maintainer/Lead Dev     |
| `dev`       | Integrasi feature (pre-release) | Semua developer         |
| `feature/*` | Task atau modul baru            | Developer masing-masing |

---

### 🧱 1.1. Penamaan Branch

Gunakan format:

```
feature/<module>-<short-description>
fix/<module>-<short-description>
chore/<scope>-<short-description>
refactor/<scope>-<short-description>
docs/<scope>
```

Contoh:

```
feature/user-registration
fix/filament-theme-darkmode
refactor/application-service-pattern
docs/laravel-boost-guidelines
```

---

### 🔄 1.2. Workflow Utama

```bash
# buat branch baru dari dev
git checkout dev
git pull origin dev
git checkout -b feature/module-name

# commit perubahan
git add .
git commit -m "feat(module): deskripsi singkat perubahan"

# push ke repo remote
git push -u origin feature/module-name

# buka Pull Request ke dev
```

---

## ✅ 2. Commit Message Convention

Gunakan format **Conventional Commit**:

```
<type>(<scope>): <deskripsi singkat>
```

| Type       | Deskripsi                               |
| ---------- | --------------------------------------- |
| `feat`     | fitur baru                              |
| `fix`      | perbaikan bug                           |
| `refactor` | restrukturisasi tanpa ubah behavior     |
| `chore`    | tugas rutin (config, dependensi, build) |
| `test`     | test baru atau update test              |
| `docs`     | update dokumentasi                      |
| `ci`       | perubahan CI/CD                         |
| `style`    | format/lint saja                        |
| `perf`     | peningkatan performa                    |

**Contoh:**

```
feat(filament): add MFA login support
fix(auth): resolve session expiration after impersonation
refactor(application): move user creation to service layer
docs(workflow): add branch naming rules
```

---

## 🧪 3. Pull Request (PR) Checklist

Sebelum membuka PR:

* [ ] Sudah **rebase** dari `dev` (bukan merge).
* [ ] Semua **test lulus**: `./vendor/bin/pest`
* [ ] Tidak ada **lint error**: `./vendor/bin/pint --test`
* [ ] Tidak ada **static error**: `./vendor/bin/phpstan analyse`
* [ ] Perubahan sudah mengikuti **Laravel Boost Guideline** (Repository/Service/Action/DTO).
* [ ] Tidak ada query mentah di Controller/Filament.
* [ ] Semua Service baru di-bind di `DomainServiceProvider`.
* [ ] Dokumentasi README / changelog diperbarui bila ada fitur baru.

**Langkah Rebase:**

```bash
git fetch origin
git rebase origin/dev
git push --force-with-lease
```

---

## 🧱 4. CI/CD Pipeline (GitHub Actions)

Pipeline otomatis akan berjalan untuk setiap PR:

1. 🧹 **Lint & Format** → `pint`
2. 🧩 **Static Analysis** → `phpstan`
3. 🧪 **Testing** → `pest`
4. ⚙️ **Build Assets** → `npm run build`
5. 🚀 **Deploy** (hanya jika merge ke `main`)

---

## 🧾 5. Review & Approval Rules

* Minimal **1 reviewer teknis** wajib menyetujui PR.
* Reviewer memeriksa:
  * Arsitektur clean (Service/Repository separation)
  * Transaksi di Service, bukan di Controller
  * Tidak ada data mentah tanpa DTO
  * Policy dan permission sudah diterapkan
  * Test coverage memadai

---

## 🧩 6. Deployment Flow

| Tahap       | Branch | Environment     |
| ----------- | ------ | --------------- |
| Development | `dev`  | Staging / Dev   |
| Production  | `main` | Live Production |

**Langkah deploy manual (opsional):**

```bash
git checkout main
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## ⚙️ 7. Release Management

Gunakan **tag semantik**:

```
v<MAJOR>.<MINOR>.<PATCH>
```

| Versi     | Makna                          |
| --------- | ------------------------------ |
| **MAJOR** | Perubahan besar / breaking     |
| **MINOR** | Fitur baru backward-compatible |
| **PATCH** | Perbaikan bug minor            |

Contoh:

```
v1.0.0 → rilis pertama stable
v1.1.0 → fitur baru ditambahkan
v1.1.1 → bugfix kecil
```

**Langkah:**

```bash
git checkout main
git pull origin main
git tag -a v1.1.0 -m "release: add new module"
git push origin v1.1.0
```

---

## 🧩 8. Environment Config Rules

| File              | Tujuan                |
| ----------------- | --------------------- |
| `.env.example`    | Template default      |
| `.env.local`      | Environment developer |
| `.env.staging`    | Testing environment   |
| `.env.production` | Server live           |

> Jangan commit `.env`!
> Tambahkan `.env*` ke `.gitignore`.

---

## 🧰 9. Branch Protection

### `main`

* 🔒 Hanya CI/CD system & maintainer yang boleh push.
* Harus lewat Pull Request dari `dev`.

### `dev`

* 🔐 Tidak boleh force-push kecuali maintainer.
* PR dari `feature/*` wajib review minimal 1 dev.

---

## 🧩 10. Developer Local Workflow

| Tujuan                 | Perintah                                          |
| ---------------------- | ------------------------------------------------- |
| Install dependensi     | `composer install && npm ci`                      |
| Generate key & migrate | `php artisan key:generate && php artisan migrate` |
| Jalankan app dev       | `php artisan serve`                               |
| Jalankan vite          | `npm run dev`                                     |
| Jalankan test          | `./vendor/bin/pest`                               |
| Lint & format          | `./vendor/bin/pint`                               |
| Static analysis        | `./vendor/bin/phpstan analyse`                    |
| Build production       | `npm run build`                                   |

---

## 🧠 11. Review Code Principles

* Hindari logika bercabang panjang di satu method.
* Setiap Service maksimal 150 baris (split bila lebih).
* Jangan query langsung di Blade, Filament Table, atau Controller.
* Gunakan helper `transaction(fn()=>...)` untuk proses kritis.
* Gunakan enum/value object untuk status & kategori.
* Audit semua perubahan penting dengan `activity()`.

---

## 🧩 12. Contoh Alur PR Sempurna

```
feature/assessment-module
 ├── DTO: AssessmentInput, AssessmentOutput
 ├── RepositoryInterface & Impl
 ├── ServiceInterface & Impl
 ├── Actions: ApproveAssessment, CalculateScore
 ├── Filament: AssessmentResource
 ├── Policy: AssessmentPolicy
 ├── Tests: Unit + Feature
```

✅ **Test Passed**
✅ **Lint OK**
✅ **Code Clean (Clean Architecture/Service Layer)**
✅ **PR reviewed by 1 developer**
✅ **Merged → dev → main (release tag)**

---

## 🧾 13. Dokumentasi Proyek

Semua proyek Laravel PTSI wajib punya:

* `/README.md` (root)
* `/docs/LARAVEL_BOOST_GUIDE.md`
* `/docs/DEVELOPER_WORKFLOW.md`
* `/CHANGELOG.md` (setiap versi)
* `/stubs/` (untuk module generator)
* `/tests/` (minimal unit test tiap use case)

---

## 📜 14. Penutup

Panduan ini dirancang untuk memastikan:

> "Semua proyek Laravel di PTSI memiliki pola, gaya, dan kualitas yang sama — terukur, modular, dan enterprise-ready."

---

📁 **File:** `docs/DEVELOPER_WORKFLOW.md`
📅 **Versi:** 1.0.0
✍️ **Author:** Divisi Teknologi Informasi – PT Surveyor Indonesia
📜 **Lisensi:** MIT License © 2025

