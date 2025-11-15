# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Phase 0: Foundation**
  - Laravel 12.x dengan struktur Clean Architecture
  - Filament 4.x dengan PTSI branding (Dark Blue, Sky Blue, Tosca Green)
  - Dark mode support dan custom Filament theme
  - Module generator command (`kit:make-module`) untuk scaffolding clean architecture
  - PHPStan static analysis integration
  - Laravel Pint untuk code formatting
  - GitHub Actions CI/CD pipeline dengan PHPStan, PHPUnit, dan Composer Security Audit
  - Composer scripts untuk development workflow (`composer run dev`)

- **Phase 1: Core Domain**
  - Database scaffolding untuk projects, epics, tickets, priorities, statuses
  - Models dan migrations untuk core domain entities
  - External access tokens untuk client portal
  - Database notifications structure

- **Phase 2: User Access Management**
  - User profile management dengan Filament Breezy
  - Role-Based Access Control (RBAC) dengan Spatie Permission
  - Filament Shield integration untuk role/permission management
  - User impersonation tools
  - Activity logging dengan Spatie Activity Log
  - Type-safe `currentUser()` helper untuk authorization checks

- **Phase 3: Project & Epic Management**
  - Project CRUD dengan Filament Resources
  - Epic management dengan relationship ke projects
  - Project notes functionality
  - Project member assignment
  - Epic sidebar menu untuk navigasi cepat

- **Phase 4: Ticket Lifecycle**
  - Ticket CRUD dengan status dan priority management
  - Ticket comments system
  - Ticket history tracking
  - Excel export/import untuk tickets (Maatwebsite Excel)
  - Ticket lifecycle workflow

- **Phase 5: Project Board & Timeline**
  - Filament Project Board dengan Tab Layout Plugin
  - Timeline view untuk project tracking
  - Board view untuk kanban-style ticket management
  - Enhanced project board dengan optimizations

- **Phase 6: Analytics Dashboards**
  - Analytics dashboard services
  - Dashboard widgets untuk statistics overview
  - Trends tracking
  - Assignment analytics
  - Responsive dashboard layout improvements

- **Phase 7: Notifications & External Portal**
  - Email notifications untuk ticket comments
  - Database notifications untuk real-time updates
  - External client portal dengan token-based authentication
  - Project member assignment/removal notifications
  - Queue worker operations guide
  - Queue support untuk async notifications

- **Phase 8: Documentation & Developer Experience**
  - Comprehensive documentation refresh
  - Operations guides untuk new modules
  - Branch protection documentation dan automation
  - Updated CI/CD configuration
  - Developer workflow documentation

### Changed
- Refactored authorization checks untuk menggunakan type-safe `currentUser()` helper
- Improved code quality dan readability across modules
- Enhanced dashboard widget responsive layout
- Optimized table performance dan queries
- Updated project board dengan better UX

### Fixed
- Resolved PHPStan static analysis errors
- Fixed PHPStan errors untuk widget `columnSpan` property
- Fixed CI failures untuk profile page tests
- Fixed lint errors dan code formatting issues
- Improved dashboard widget responsive layout

### Documentation
- Added queue worker operations guide
- Refreshed README dan operations guides untuk new modules
- Added branch protection documentation
- Updated Laravel Boost guide
- Enhanced developer workflow documentation

---

## [1.0.0] - 2025-11-15

### Initial Release
- Complete project management system dengan Clean Architecture
- Full CRUD untuk Projects, Epics, Tickets
- User access management dengan RBAC
- Analytics dashboards
- External client portal
- Notification system
- Excel import/export
- Comprehensive documentation

[Unreleased]: https://github.com/marloxxx/ptsi-project-management/compare/v1.0.0...dev
[1.0.0]: https://github.com/marloxxx/ptsi-project-management/releases/tag/v1.0.0

