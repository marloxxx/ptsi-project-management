# Architecture Documentation

## Overview

KaidoKit v4 PTSI Edition menggunakan **Clean Architecture** dengan **Layered Architecture Pattern**. Arsitektur ini dirancang untuk:

1. **Maintainability** - Mudah dipelihara dan dikembangkan
2. **Testability** - Mudah di-test dengan unit test
3. **Flexibility** - Mudah beradaptasi dengan perubahan
4. **Scalability** - Mudah di-scale sesuai kebutuhan

---

## Layer Structure

```
┌─────────────────────────────────────────────────┐
│           Presentation Layer (UI)               │
│         Filament Resources & Pages              │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────┐
│         Application Layer (Use Cases)           │
│    Services │ Actions │ Policies               │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────┐
│         Contracts Layer (Interfaces)            │
│    Repository Interfaces │ Service Interfaces   │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────┐
│      Infrastructure Layer (External)            │
│   Repository Impl │ External Services           │
└─────────────────────────────────────────────────┘
```

---

## 1. Contracts Layer

**Path:** `app/Domain/`

Contracts layer berisi **interface** dan **contract** yang mendefinisikan kontrak antar layer.

### Repositories (Interfaces)

```php
// app/Domain/Repositories/UserRepositoryInterface.php
interface UserRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

**Prinsip:**
- ❌ **TIDAK** boleh ada implementasi konkrit
- ✅ Hanya interface/contract
- ✅ Tidak ada dependency ke framework
- ✅ Pure PHP interfaces
- ✅ Menjadi kontrak antara Application dan Infrastructure layer

### Services (Interfaces)

```php
// app/Domain/Services/UserServiceInterface.php
interface UserServiceInterface
{
    public function create(array $data, ?array $roles = null): User;
    public function update(int $id, array $data, ?array $roles = null): bool;
}
```

**Prinsip:**
- Mendefinisikan **kontrak service** yang harus diimplementasi
- Menggunakan **array** untuk input data (validasi dilakukan di layer UI/Controller)
- Tidak ada dependency ke framework
- Murni interface definition

---

## 2. Application Layer

**Path:** `app/Application/`

Application layer berisi **implementasi** dari business logic.

### Services (Implementation)

```php
// app/Application/Services/UserService.php
class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}
    
    public function create(array $data, ?array $roles = null): User
    {
        return DB::transaction(function () use ($data, $roles) {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }
            
            $user = $this->repository->create($data);
            
            // Assign roles if provided
            if ($roles) {
                $user->syncRoles($roles);
            }
            
            // Business logic here
            activity()
                ->performedOn($user)
                ->log('User created');
            
            return $user;
        });
    }
}
```

**Prinsip:**
- ✅ Dependency injection via constructor
- ✅ Gunakan **transaction** untuk operasi multi-step
- ✅ Return **Model** langsung (atau bool untuk update/delete)
- ✅ Log activity untuk audit trail
- ✅ Validasi dilakukan di **Form Schema** (Filament) atau **Form Request** (API)

### Policies

```php
// app/Application/Policies/UserPolicy.php
class UserPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $user): bool
    {
        return $user->can('view_user');
    }
    
    public function update(User $user, User $model): bool
    {
        return $user->can('update_user') || $user->id === $model->id;
    }
}
```

**Prinsip:**
- ✅ Authorization logic terpisah dari controller
- ✅ Gunakan Spatie Permission untuk role checking
- ✅ Policy per model

### Actions (Optional)

```php
// app/Application/Actions/SendWelcomeEmail.php
class SendWelcomeEmail
{
    public function execute(User $user): void
    {
        Mail::to($user)->send(new WelcomeEmail($user));
    }
}
```

**Prinsip:**
- Single responsibility
- Reusable logic
- Clear naming (`execute()`, `handle()`)

---

## 3. Infrastructure Layer

**Path:** `app/Infrastructure/`

Infrastructure layer berisi **implementasi konkrit** dari interface contracts.

### Repositories (Implementation)

```php
// app/Infrastructure/Repositories/UserRepository.php
class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::all();
    }
    
    public function find(int $id)
    {
        return User::find($id);
    }
    
    public function create(array $data)
    {
        return User::create($data);
    }
    
    public function update(int $id, array $data): bool
    {
        return User::where('id', $id)->update($data);
    }
    
    public function delete(int $id): bool
    {
        return User::destroy($id);
    }
}
```

**Prinsip:**
- ✅ Eloquent implementation
- ✅ Query optimization
- ✅ Eager loading jika perlu
- ✅ Caching jika perlu

### External Services

```php
// app/Infrastructure/Services/PaymentGatewayService.php
class PaymentGatewayService
{
    public function __construct(
        protected HttpClient $client
    ) {}
    
    public function charge(array $data): array
    {
        $response = $this->client->post('/charge', $data);
        return $response->json();
    }
}
```

**Prinsip:**
- External API integration
- Third-party service integration
- Abstraction untuk testing

---

## 4. Presentation Layer (Filament)

**Path:** `app/Filament/`

Presentation layer hanya untuk **UI** dan **user interaction**.

### Resources

```php
// app/Filament/Resources/UserResource.php
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('password')->password()->required(),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('created_at')->dateTime(),
        ]);
    }
}
```

**Prinsip:**
- ❌ **JANGAN** taruh business logic di Resource
- ✅ Call **Service** untuk business logic
- ✅ Form validation di form schema
- ✅ Authorization via Policy

### Actions dengan Service

```php
Actions::make([
    Action::make('approve')
        ->requiresConfirmation()
        ->databaseTransaction()
        ->action(function ($record) {
            app(UserServiceInterface::class)->update(
                $record->id,
                ['status' => 'approved']
            );
        })
])
```

---

## Dependency Injection

### Register Bindings

Edit `app/Providers/DomainServiceProvider.php`:

```php
public function register(): void
{
    // Repository bindings
    $this->app->bind(
        UserRepositoryInterface::class,
        UserRepository::class
    );
    
    // Service bindings
    $this->app->bind(
        UserServiceInterface::class,
        UserService::class
    );
}
```

### Usage

```php
// Automatic injection
public function __construct(
    protected UserServiceInterface $service
) {}

// Manual resolution
$service = app(UserServiceInterface::class);
```

---

## Transaction Management

### Database Transactions

```php
use Illuminate\Support\Facades\DB;

public function process($input)
{
    return DB::transaction(function () use ($input) {
        // All database operations here
        $user = $this->repository->create($data);
        $profile = $this->profileRepository->create($profileData);
        
        // If any exception thrown, all rolled back
        return $user;
    });
}
```

### Pessimistic Locking

```php
// Lock for update (prevent concurrent modifications)
$user = User::where('id', $id)
    ->lockForUpdate()
    ->first();
    
$user->balance -= $amount;
$user->save();
```

### Idempotency

```php
// Prevent duplicate operations
$key = "process:{$orderId}";

if (Cache::has($key)) {
    throw new DuplicateOperationException();
}

Cache::put($key, true, 600); // 10 minutes

// Process...
```

---

## Testing Strategy

### Unit Tests (Domain/Application)

```php
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_register_creates_user(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->andReturn(new User(['id' => 1, 'name' => 'John']));
            
        $service = new UserService($repository);
        $user = $service->create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'password'
        ]);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->name);
    }
}
```

### Feature Tests (Infrastructure/Filament)

```php
public function test_user_can_create_ticket(): void
{
    $this->actingAs($this->admin)
        ->post('/admin/tickets', [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
        ])
        ->assertRedirect();
        
    $this->assertDatabaseHas('tickets', [
        'title' => 'Test Ticket',
    ]);
}
```

---

## Best Practices

### ✅ DO

1. **Interface-First**: Selalu buat interface dulu di Contracts layer, implementasi kemudian
2. **Validation**: Validate di Form Schema (Filament) atau Form Request (API)
3. **Transaction**: Wrap multi-step operations dalam transaction
4. **Logging**: Log semua perubahan penting dengan Activity Log
5. **Authorization**: Gunakan Policy untuk authorization
6. **Testing**: Write tests untuk business logic
7. **Layer Separation**: Jangan biarkan layer saling bergantung langsung

### ❌ DON'T

1. **No Business Logic in Controllers/Resources**: Controllers hanya routing
2. **No Direct Model Access in Services**: Gunakan Repository
3. **No Framework Dependency in Contracts Layer**: Contracts layer harus pure PHP
4. **No God Classes**: Split responsibilities
5. **No Magic**: Explicit > Implicit
6. **No Tight Coupling**: Depend on abstractions, not concretions

---

## Module Generation Workflow

```bash
# 1. Generate module skeleton
php artisan kit:make-module Order \
  --with-repository \
  --with-service \
  --with-policy \
  --with-filament

# 2. Create model & migration
php artisan make:model Order -m

# 3. Implement repository
# Edit: app/Infrastructure/Repositories/OrderRepository.php

# 4. Implement service
# Edit: app/Application/Services/OrderService.php

# 5. Register bindings
# Edit: app/Providers/DomainServiceProvider.php

# 6. Implement Filament resource
# Edit: app/Filament/Resources/OrderResource.php

# 7. Generate permissions
php artisan shield:generate --all

# 8. Test!
php artisan test
```

---

## Scaling Considerations

### Horizontal Scaling

- Gunakan **Redis** untuk cache & session
- Gunakan **Queue** untuk heavy operations
- Separate **read** dan **write** database (optional)

### Vertical Scaling

- **Laravel Octane** untuk performance boost
- **Database indexing** untuk query optimization
- **Eager loading** untuk N+1 prevention

### Code Organization

- Split besar modules ke **smaller services**
- Gunakan **Service Layer** untuk complex operations
- Gunakan **Form Schema** (Filament) atau **Form Request** (API) untuk validasi

---

**Maintained by PTSI Digital Team**

