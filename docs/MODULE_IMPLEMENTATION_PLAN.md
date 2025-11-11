# PTSI Project Management – Module Implementation Plan

## Overview
This plan tracks how features from the DewaKoding Project Management system will be integrated into the PTSI Laravel starter kit baseline. Each module includes objectives, required dependencies, data structures, service-layer work, UI work, and testing notes.

## Phase 0 – Foundation
- [x] Align composer dependencies (Excel, Octane, tab layout plugin) with starter kit baselines
- [x] Configure environment variables (.env.example) for branding and timezone defaults
- [ ] Migrate Docker/Vite configuration deltas if needed

## Module Plans

### 1. Core Domain & Settings
- **Objectives**
  - Scaffold database structures for projects, epics, tickets, priorities, statuses, notifications, and external access tokens.
  - Port system configuration/settings into PTSI Settings module.
- **Schema Snapshot**
  - `projects`: name, description, ticket_prefix (unique), color (hex), start_date, end_date, pinned_at, timestamps, indexes on `ticket_prefix`, date ranges, and `pinned_at`.
  - `project_members`: project/user pivot with timestamps and unique pairing; secondary indexes on `project_id` and `user_id`.
  - `project_notes`: project reference, author (`created_by`), title, long-form content, note_date, timestamps.
  - `ticket_statuses`: project reference, name, color, `is_completed`, `sort_order`, timestamps, composite index on `(project_id, sort_order)`.
  - `ticket_priorities`: global catalog with name, color, optional `sort_order`, timestamps.
  - `tickets`: project, status, priority, epic, creator, UUID, name, long description, `start_date`, `due_date`, timestamps, indexes supporting board filters (`project_id`, `ticket_status_id`, `priority_id`, `due_date`).
  - `ticket_users`: assignment pivot with timestamps, unique `(ticket_id, user_id)` plus supporting indexes.
  - `ticket_histories`: ticket, user, status transition snapshot, timestamps.
  - `ticket_comments`: ticket, user, long comment body, timestamps.
  - `epics`: project, name, description, `start_date`, `end_date`, `sort_order`, timestamps.
  - `external_access_tokens`: project reference, access_token, password hash, `is_active`, `last_accessed_at`, timestamps.
- **Tasks**
  - [x] Recreate migrations using clean architecture folder conventions.
  - [x] Implement repositories/services under `Domain` and `Application` namespaces.
  - [x] Seed base data (ticket priorities, default project status presets).
- **Testing**
  - [x] Feature tests covering project/ticket services (default statuses, external access, history & comments).

### 2. User & Access Management
- **Objectives**
  - Integrate Google OAuth login and maintain Filament Shield role/permission parity.
  - Ensure PTSI branding and MFA remain functional.
- **Tasks**
  - [ ] Port Shield policies and sync with starter kit roles.
  - [ ] Create super admin provisioning command/seed if necessary.
- **Testing**
  - [ ] Authentication tests (local login, Google OAuth stub, MFA availability).

### 3. Project & Epic Management
- **Objectives**
  - Provide CRUD interfaces for projects and epics with custom fields (ticket prefix, timelines, team members).
- **Tasks**
  - [ ] Build repositories/services for Projects & Epics, including assignment logic.
  - [ ] Create Filament Resources with forms and table schemas.
  - [ ] Implement project notes and audit logging via starter kit activity log.
- **Testing**
  - [ ] Filament resource tests for create/edit/list flows.

### 4. Ticket Lifecycle
- **Objectives**
  - Manage tickets, priorities, statuses, history, and multi-assignee workflow.
- **Tasks**
  - [x] Implement ticket service (creation, assignment, status transitions, history entries).
  - [ ] Port comments Livewire component and integrate with Filament relation managers.
  - [ ] Configure exports/imports (Excel) and unique ticket identifier generator.
- **Testing**
  - [ ] Feature tests for ticket creation, assignment, status updates, and history tracking.
  - [ ] Tests for Excel import/export commands.

### 5. Boards & Timeline Views
- **Objectives**
  - Deliver Kanban board and timeline visualization inside Filament.
- **Tasks**
  - [ ] Rebuild board page leveraging Filament page architecture and tab layout plugin.
  - [ ] Implement timeline chart widgets and ensure data aggregation services exist.
  - [ ] Optimize queries for large datasets (eager loading, caching where needed).
- **Testing**
  - [ ] Livewire/Filament tests covering board interactions and timeline rendering responses.

### 6. Analytics & Dashboards
- **Objectives**
  - Port leaderboard, stats overview, project status charts, and user contribution widgets.
- **Tasks**
  - [ ] Implement dedicated Application services for aggregations.
  - [ ] Create Filament widgets/dashboard sections with PTSI theming.
- **Testing**
  - [ ] Widget feature tests verifying metric counts and chart datasets.

### 7. Notifications & External Portal
- **Objectives**
  - Restore email notifications, queue processing, and external client dashboard.
- **Tasks**
  - [ ] Port mail templates and notification dispatch logic to starter kit services.
  - [ ] Configure queue worker documentation and Supervisor sample under PTSI ops.
  - [ ] Integrate `ExternalLogin` and `ExternalDashboard` Livewire components with PTSI layouts.
- **Testing**
  - [ ] Notification tests (queue dispatch assertions) and external portal route/component tests.

### 8. Documentation & Dev Experience
- **Objectives**
  - Update docs to reflect PTSI ownership and new setup steps.
- **Tasks**
  - [ ] Align README, Quick Start, and architecture docs with new modules.
  - [ ] Provide deployment checklist (queues, OAuth, Octane optional).
  - [ ] Add change log entries summarizing migration from Dewakoding base.

## Tracking & Milestones
- Milestone 1: Foundation + Core Domain completed and tested.
- Milestone 2: Project/Epic + Ticket lifecycle operational with Filament resources.
- Milestone 3: Boards, analytics, and external portal delivered.
- Milestone 4: Notifications, docs, and final QA.

## Open Questions
- Do we need additional PTSI-specific compliance checks (audit logging, data retention)? **Yes**
- Should external portal support multi-tenant branding per client? **Yes**
- Confirm preferred default roles/permissions mapping between Dewakoding and PTSI hierarchy. **Yes**

