## Review guidelines
- Confirm Laravel conventions are followed: controllers stay thin, business logic lives in services/actions, and Form Requests handle validation.
- Ensure PHP files declare strict types where expected, use constructor property promotion, and include explicit parameter and return type hints.
- New or updated endpoints and Livewire/Filament components must enforce authorization via policies or guards and avoid calling `env()` outside configuration files.
- Database migrations must be reversible, preserve existing column attributes when modifying tables, and avoid data loss. Seeders and factories should remain deterministic.
- All meaningful changes should ship with focused PHPUnit or Livewire/Filament feature tests. Ask for tests when behaviour could regress.
- Code must remain formatted with `vendor/bin/pint --dirty` and obey existing naming conventions. Flag any deviations.

## app/
- Check that services, actions, and repositories respect the layered architecture described in `ARCHITECTURE.md`.
- Filament resources should rely on policies, use relationship-based form fields, and avoid N+1 queries by eager loading records.
- Livewire components must keep state on the server, use `wire:key` inside loops, and leverage lifecycle hooks (`mount`, `updatedFoo`) appropriately.

## database/
- Migrations should include matching down() implementations and refrain from destructive operations without backups. Use proper column types and indexes.
- Factories need realistic defaults and should avoid hard-coded IDs. Seeders must not duplicate production-only logic.

## resources/
- Blade templates should remain accessible (labels, aria attributes) and defer heavy logic to PHP classes. JavaScript interactions should live in `resources/js`.
- CSS changes should be scoped to utility classes and respect existing Tailwind configuration.

## tests/
- Feature tests should cover authentication/authorization paths and verify Filament tables, forms, and actions using Livewire testing utilities.
- Unit tests must isolate domain logic with meaningful assertions and leverage factories for model creation.

