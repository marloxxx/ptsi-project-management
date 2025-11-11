# Contributing Guide

Thank you for considering contributing to KaidoKit v4 PTSI Edition!

## Getting Started

1. Fork the repository
2. Clone your fork
3. Create a new branch from `develop`
4. Make your changes
5. Run tests and lint
6. Submit a pull request

## Development Setup

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Start development
composer run dev
```

## Code Standards

### PHP Coding Standards

We follow **Laravel Pint** standards:

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### Architecture Rules

1. **Interface-First**: Always create interface before implementation
2. **No Business Logic in Controllers**: Use Services
3. **DTO for Type Safety**: Use DTOs for complex data structures
4. **Transaction for Multi-Step**: Wrap multi-step operations in transactions
5. **Policy for Authorization**: Use Laravel Policies

### Naming Conventions

```php
// Interfaces
UserRepositoryInterface.php
UserServiceInterface.php

// Implementations
UserRepository.php (Infrastructure)
UserService.php (Application)

// DTOs
UserInputDTO.php
UserOutputDTO.php

// Policies
UserPolicy.php

// Actions (optional)
SendWelcomeEmail.php
CreateInvoice.php
```

## Testing

### Run Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=UserServiceTest

# With coverage
php artisan test --coverage
```

### Writing Tests

```php
// Unit Test (Service)
class UserServiceTest extends TestCase
{
    public function test_creates_user(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        // ... mock setup
        
        $service = new UserService($repository);
        $result = $service->create($input);
        
        $this->assertTrue($result->success);
    }
}

// Feature Test (API/UI)
class UserFeatureTest extends TestCase
{
    public function test_user_can_register(): void
    {
        $this->post('/api/register', [...])
            ->assertSuccessful();
    }
}
```

## Pull Request Process

1. **Update Documentation**: Update README.md if needed
2. **Add Tests**: Add tests for new features
3. **Run Lint**: Ensure code passes lint
4. **Update CHANGELOG**: Add entry to CHANGELOG.md
5. **Target `develop` branch**: Always PR to `develop`, not `main`

### PR Title Format

```
feat: Add user export feature
fix: Fix permission check in UserPolicy
docs: Update architecture documentation
refactor: Refactor UserService for better testability
test: Add tests for OrderService
```

### PR Description Template

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
How to test the changes

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] Code follows style guidelines
- [ ] All tests passing
```

## Git Workflow

### Branch Naming

```
feature/user-export
fix/permission-bug
docs/architecture-guide
refactor/user-service
```

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add user export feature
fix: resolve permission check bug
docs: update architecture guide
refactor: simplify UserService logic
test: add UserService tests
chore: update dependencies
```

## Code Review

### Reviewers Will Check

1. ✅ Code follows architecture guidelines
2. ✅ Tests are included and passing
3. ✅ Documentation is updated
4. ✅ No breaking changes (or properly documented)
5. ✅ Performance considerations addressed

### Response Time

- Initial review: Within 2 business days
- Follow-up: Within 1 business day

## Issue Reporting

### Bug Reports

Use the bug report template:

```markdown
**Describe the bug**
A clear description of the bug

**To Reproduce**
Steps to reproduce

**Expected behavior**
What you expected

**Screenshots**
If applicable

**Environment**
- PHP version:
- Laravel version:
- Filament version:
```

### Feature Requests

```markdown
**Problem Statement**
What problem does this solve?

**Proposed Solution**
Your proposed solution

**Alternatives Considered**
Other solutions you considered

**Additional Context**
Any other context
```

## Questions?

- 📧 Email: ti@ptsi.co.id
- 💬 Discussion: GitHub Discussions
- 🐛 Issues: GitHub Issues

---

**Thank you for contributing! 🎉**

