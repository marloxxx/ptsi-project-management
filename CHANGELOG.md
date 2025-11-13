# Changelog

All notable changes to Laravel Starter Kit PTSI will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-11-13

### Added
- 📊 Analytics dashboard widgets (stats overview, ticket trends, user distribution, recent activity).
- 🗂️ Project board & timeline (kanban + Gantt) dengan Solution Forest Tab Layout plugin.
- 🌐 External client portal (token login, dashboard publik dengan filter & aktivitas).
- ✉️ Queued notifications untuk komentar tiket & perubahan anggota proyek (email + in-app).
- 📚 Dokumentasi Phase 8: README, Quick Start, Developer Workflow, Deployment (queue/Supervisor) diperbarui.

### Changed
- ♻️ `composer run dev` kini menjalankan server, queue worker, Vite, dan log stream bersamaan.
- 🧾 Deployment guide ditambah contoh konfigurasi Supervisor untuk queue & rename penomoran section.
- 🗒️ Module implementation plan diperbarui untuk mencerminkan phase 5–8.

### Testing
- ✅ Pemeriksaan manual `php artisan test --filter=` untuk modul tiket, notifikasi, dan portal eksternal.
- ⚠️ Catatan: Tes Livewire memerlukan asset Filament; jalankan `npm run build` sebelum test penuh.

---

## [1.0.0] - 2025-11-06

### Added
- 🎉 Initial release of Laravel Starter Kit PTSI Edition
- ✅ Laravel 12 + Filament 4.2 (latest versions)
- ✅ Clean Architecture (Domain/Application/Infrastructure layers)
- ✅ Module Generator Command (`kit:make-module`)
- ✅ Interface-First Pattern with DomainServiceProvider
- ✅ DTO Pattern (Input/Output DTOs)
- ✅ PTSI Brand Colors & Dark Mode
- ✅ Comprehensive Documentation (README, ARCHITECTURE, SETUP, FEATURES, CONTRIBUTING)

### Security & Authentication
- ✅ Filament Shield 4.0 (RBAC)
- ✅ Filament Breezy 3.0 (User Profile & MFA)
- ✅ Two-Factor Authentication (2FA)
- ✅ User Impersonation (Filament Impersonate 4.0)
- ✅ Spatie Permission 6.23
- ✅ Laravel Sanctum 4.2 (API Authentication)
- ✅ Activity Logging (Spatie Activity Log 4.10)

### Features
- ✅ Media Library (Spatie Media Library 11.17)
- ✅ Settings Management (Spatie Settings 3.5)
- ✅ Excel Import/Export (Filament Excel 3.2)
- ✅ Database Notifications
- ✅ API Documentation (Scramble 0.13)
- ✅ Blade FontAwesome Icons

### Developer Tools
- ✅ Laravel Boost 1.7 (AI Development Assistant)
- ✅ Laravel Debugbar 3.16
- ✅ Laravel Blueprint 2.12
- ✅ Laravel Pint 1.24
- ✅ Laravel Sail 1.47
- ✅ PHPUnit 11.5

### DevOps
- ✅ GitHub Actions CI/CD Pipeline
- ✅ Docker Support (Laravel Sail)
- ✅ Composer Scripts (setup, dev, test)

### Removed
- ❌ Filament Socialite (Social Login) - Removed per requirements
- ❌ Pest PHP - Skipped due to PHPUnit version conflict

### Changed
- ⬆️ All packages updated to latest compatible versions for Filament 4
- 📝 Project renamed from `kaidokit-v4-ptsi` to `laravel-starter-kit-ptsi`

---

## Upgrade Notes

### From Kaido Kit v3 to PTSI Starter v1.0

**Major Version Upgrades:**
- Filament: `3.2` → `4.2`
- Shield: `3.3` → `4.0`
- Breezy: `2.4` → `3.0`
- Impersonate: `3.15` → `4.0`
- Excel: `2.3` → `3.0`

**Breaking Changes:**
- Filament v4 has breaking changes from v3. See [Filament Upgrade Guide](https://filamentphp.com/docs/4.x/upgrade-guide)
- Clean architecture requires different folder structure
- Service binding now done via DomainServiceProvider

**Migration Path:**
1. Follow Filament v4 upgrade guide
2. Restructure code to clean architecture
3. Update service bindings in DomainServiceProvider
4. Regenerate permissions with `shield:generate`

---

## Future Roadmap

### v1.2.0 (Planned)
- [ ] Multi-tenancy support (optional)
- [ ] Localization (i18n) support
- [ ] Email template system (customisable templates)
- [ ] Performance monitoring integration (Horizon, OpenTelemetry)

### v1.3.0 (Planned)
- [ ] API Resource generation in module generator
- [ ] GraphQL support (optional)
- [ ] Real-time features (Laravel Reverb)
- [ ] Advanced reporting module

### v2.0.0 (Future)
- [ ] Microservices architecture support
- [ ] Event-driven architecture
- [ ] CQRS pattern implementation
- [ ] Advanced Clean Architecture patterns

---

**Maintained by**: PTSI Digital Team  
**License**: MIT  
**Repository**: https://github.com/ptsi-digital/laravel-starter-kit-ptsi

