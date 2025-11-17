## PTSI Module Implementation Plan (Internal-Only)

This plan aligns the existing application with PTSI’s internal, private project management needs while keeping parity with modern tools (Jira-like) where valuable. It builds on current code (Laravel 12, Filament 4, Livewire 3) and the layered architecture already in use (Domain → Application → Infrastructure → Interface).

### 1) Principles & Constraints (PTSI-Internal)
- Privacy-first: no public endpoints or data exposure; external access is explicitly whitelisted and auditable.
- Least-privilege RBAC: policies and per-project roles govern every action.
- Auditability by default: every state change is recorded and attributable.
- Performance at scale: use eager loading, indexing, caching on analytics, and queues for heavy work.
- Zero `env()` in code; use config and settings; follow existing architecture and naming conventions.
- Tests for all meaningful changes (feature + unit), formatted with Pint.

### 2) Current State (High-Level)
- Core entities: `Project`, `Ticket`, `TicketStatus`, `Epic`, `ProjectNote`, `User` (with permissions), `TicketHistory`, `TicketComment`, priorities/statuses, exports/imports.
- Filament: Project Resource, Tickets Resource, Board page (`ProjectBoard`), Epics page, dashboard/widgets.
- Notifications: project member assigned/removed, ticket comment added/updated.
- External: `ExternalAccessToken` + basic external Livewire pages (login/dashboard).
- Gaps vs Jira-like: no Sprint entity; no burndown/velocity/CFD; limited automations; limited saved filters/global search; no formal SLA/escalation; custom fields not per-project; no dependency graph/Gantt; reporting is basic.

### 3) Roadmap (Phased, Safe-by-Default)
1. Sprints & Board integration (drag-and-drop by sprint; burndown/velocity).
2. Workflow transitions per project (guarded by policy).
3. Issue types, sub-tasks, and dependencies.
4. Custom fields per project (schema + values).
5. Automations (internal only; Slack/Teams optional via outbound webhook to PTSI infra).
6. Saved filters & global search (internal).
7. Reporting & Analytics (CFD, lead/cycle time, throughput).
8. Service Desk (internal restricted or token-based guest with strict rate limits).
9. Public API (internal tokens only; IP allowlist) & webhooks.
10. SLA + escalation.

Each phase ships with migrations, services, policies, Filament UI, and tests. No destructive migrations; always reversible.

---

## Module Specifications

### A) Sprints & Board

Scope:
- Add `Sprint` entity scoped to `Project` with dates, goal, and state (Planned, Active, Closed).
- Assign tickets to sprints; filter board by sprint; allow drag-and-drop between statuses within the selected sprint.
- Add burndown and velocity widgets per sprint/project.

Data Model:
- `sprints`:
  - id, project_id (FK, indexed), name, goal, state enum, start_date, end_date, closed_at, created_by, timestamps.
- `tickets`: add nullable `sprint_id` (FK) with index.

Domain & Application:
- Services: `SprintServiceInterface` + `SprintService` (create/update/close/reopen, assign tickets, compute metrics).
- Board integration via `TicketBoardServiceInterface`: add sprint scoping.

Policies:
- Only project members with role `Maintainer|Admin` can create/activate/close sprint; contributors can assign tickets they own or are allowed to move.

Filament UI:
- Project → View: Sprints relation manager (list/create/edit/close).
- Board page: Sprint selector; drag-and-drop scoped to sprint; quick-assign sprint from card menu.
- Widgets: Sprint Burndown, Sprint Velocity; Project dashboard shows current sprint metrics if any.

Reporting:
- Burndown dataset from remaining story points or ticket count; velocity from completed points per sprint.

Tests (Feature + Unit):
- Create/activate/close sprint; assign ticket to sprint; board filtering; burndown calculation; velocity stability across sprints; policy enforcement.

Acceptance Criteria:
- Can create and activate one active sprint per project.
- Board shows tickets filtered by sprint quickly (<300ms server time for typical project sizes).
- Burndown/velocity widgets render correct datasets for the selected sprint.


### B) Workflow Transitions per Project

Scope:
- Configurable status transitions per project; enforce guards via policy (role/ownership/conditions).

Data Model:
- `project_workflows` (project_id, definition JSON for allowed transitions).

Application:
- `TicketService::transitionStatus()` validates transition against workflow + policy; records `TicketHistory` entry.

Filament:
- Project settings tab to edit allowed transitions with a simple matrix UI.

Tests:
- Transition allowed/denied paths; history entries; policy rules.

Acceptance Criteria:
- Illegal transitions are blocked with clear feedback; all transitions are audited.


### C) Issue Types, Sub-Tasks, Dependencies

Scope:
- Add `issue_type` (Bug/Task/Story/Epic link), parent-child sub-tasks, and dependency graph (blocks/relates).

Data Model:
- `tickets`: enum/string `issue_type`, `parent_id` nullable (self FK, indexed).
- `ticket_dependencies`: (ticket_id, depends_on_ticket_id, type enum: blocks/relates).

Filament:
- Ticket form fields for issue type, parent, dependencies; table columns; quick actions.

Tests:
- Parent-child visibility; dependency constraints (prevent closing a ticket if blocked unless override permitted).

Acceptance Criteria:
- Sub-tasks and dependencies visible on ticket view; basic protections enforced.


### D) Custom Fields per Project

Scope:
- Admin project dapat menambah custom fields (text/number/select/date) dan mengatur tampilannya di form/tabel.

Data Model:
- `project_custom_fields`: (project_id, key, label, type, options JSON, required, order, active).
- `ticket_custom_values`: (ticket_id, custom_field_id, value JSON).

Application:
- Dynamic form schema generation; casting/validation from field type; indexing strategy for searchable fields (select/number/date).

Tests:
- Create fields; fill and persist; filter/sort by custom field where applicable.

Acceptance Criteria:
- Non-breaking dynamic fields integrated in ticket form and can be exported.


### E) Automations (Internal-Only)

Scope:
- Declarative rules: on create/update/comment → actions (set field, reassign, change status, add label, post Slack).

Data Model:
- `automation_rules`: (project_id, event, conditions JSON, actions JSON, active).

Runtime:
- Listeners/Jobs execute actions; retries on transient errors; idempotency keys per event.

Security:
- Outbound webhooks restricted to PTSI endpoints; secrets in config/settings; thoroughly logged.

Tests:
- Condition matching; action execution; policy interaction; webhook signing.

Acceptance Criteria:
- Rules execute reliably with audit logs; failure paths are visible and retryable.


### F) Saved Filters & Global Search

Scope:
- Saved filters (per user/team/project) + shareable; global search across tickets/comments with permissions.

Data Model:
- `saved_filters`: (owner_type, owner_id, name, query JSON, visibility).

Search:
- Start with optimized DB queries + indexes; optionally integrate Scout/Meilisearch later (internal).

Tests:
- Save/share filter; apply on board/table; permission checks; search relevance sanity.

Acceptance Criteria:
- Users can quickly recall and share powerful views without performance degradation.


### G) Reporting & Analytics

Scope:
- Burndown, Velocity, Cumulative Flow Diagram, Lead/Cycle time, Throughput.

Implementation:
- Application services generate datasets with caching; Filament widgets/charts render views.

Tests:
- Dataset correctness against seeded fixtures; cache invalidation on updates.

Acceptance Criteria:
- Charts render in <250ms cached; data correctness validated by tests.


### H) SLA & Escalation (Internal)

Scope:
- SLA targets per priority/project; breach detection; escalation notifications; pause on “waiting for customer”.

Data Model:
- `sla_policies`: (project_id, priority_id, response_mins, resolve_mins, pause_on_statuses JSON).

Runtime:
- Scheduler job evaluates breaches; sends internal notifications and optional reassignments.

Tests:
- Breach detection; pausing logic; notifications.

Acceptance Criteria:
- Breaches are detected/persisted; alerting/escalation flows are traceable.


### I) Service Desk (Restricted)

Scope:
- Optional internal/customer portal with token-based limited access; no public indexing; strict rate limits and expiry.

Security:
- All actions gated; audit for every request; file visibility remains private.

Acceptance Criteria:
- External visibility never exceeds explicit grants; rate limits enforced.


---

## Cross-Cutting Concerns

- Authorization: Strengthen Policies per entity; project-role pivot; always check in Actions/Services.
- Audit: Extend `TicketHistory` coverage (custom fields, dependencies, workflow, automation effects).
- Performance: Eager load on Filament tables/board; add missing DB indexes; cache analytics.
- Migrations: Reversible; preserve existing attrs; backfill nullable columns safely; no data loss.
- Testing: PHPUnit feature tests for Filament pages/actions, Livewire components; unit tests for services; use factories/states.
- Formatting: Run `vendor/bin/pint --dirty` and fix lints before merging.

---

## Deliverables per Phase

- Migrations + Models (with casts(), relationships, indexes).
- Domain Contracts + Application Services (interfaces + implementations).
- Policies + Gates.
- Filament Resources/Pages/Widgets with eager loading and relationship fields.
- Notifications (where applicable).
- Feature + Unit Tests with realistic factories.

---

## Rollout & Change Management

1) Dev: implement + tests pass; Pint clean.
2) Staging: seed demo data; verify migrations; UAT checks on permissions and audit.
3) Production: timed deployment window; backup DB; run migrations; warm caches for analytics.
4) Post-deploy: monitor logs and performance; toggle feature flags as needed.

---

## Phase Status

### ✅ Phase 1: Sprints & Board Integration (COMPLETED)
1. ✅ Add `sprints` table + `tickets.sprint_id` (nullable) with indexes.
2. ✅ Implement `SprintServiceInterface` + `SprintService` (activate/close, assign tickets, metrics).
3. ✅ Board page: add sprint selector and sprint-scoped drag-and-drop.
4. ✅ Add Sprint Burndown and Velocity widgets in Project View/Dashboard.
5. ✅ Tests covering sprint lifecycle, board filtering, metrics, and authorization.

### ✅ Phase 2: Workflow Transitions per Project (COMPLETED)
1. ✅ Add `project_workflows` table with JSON definition for allowed transitions.
2. ✅ Create `ProjectWorkflow` model and repository.
3. ✅ Update `TicketService::changeStatus()` to validate transitions against workflow + policy.
4. ✅ Create Filament UI for editing workflow transitions in Project settings.
5. ✅ Tests covering transition validation (allowed/denied paths), history entries, and policy rules.

### 🚧 Phase 3: Issue Types, Sub-Tasks, and Dependencies (PENDING)

## Immediate Next Steps (Phase 3)

1. Add `issue_type` field to tickets table.
2. Add `parent_id` nullable FK for sub-tasks.
3. Create `ticket_dependencies` table for dependency graph.
4. Update TicketService to handle parent-child relationships and dependencies.
5. Create Filament UI for managing issue types, sub-tasks, and dependencies.
6. Tests covering parent-child relationships and dependency constraints.

All changes will follow existing conventions in `app/Application/Services`, `app/Domain/Services`, `app/Infrastructure`, `app/Filament`, and Policies, with thorough tests under `tests/Feature` and `tests/Unit`.


