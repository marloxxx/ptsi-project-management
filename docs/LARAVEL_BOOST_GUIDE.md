# ⚡ Laravel Boost Guidelines — PTSI Edition

### Clean Architecture · Layered Pattern · Filament 4 Ready · Laravel 12

---

## 🎯 Tujuan

Dokumen ini menjadi **acuan teknis dan gaya pengembangan** untuk semua proyek Laravel internal PTSI, agar setiap developer:

* Menulis kode **bersih, konsisten, dan terukur**.
* Menggunakan **Laravel Boost Structure** (Repository + Service + Action + Policy).
* Menghindari *spaghetti code* di Controller/Filament.
* Siap untuk **scaling, testing, dan multi-tenant environment**.

---

## 🧱 1. Arsitektur Folder Standar

```
app/
  Domain/               # Contracts & Interfaces (tidak ada implementasi)
    Repositories/       # Repository interfaces
    Services/           # Service interfaces
  Application/          # Use-case implementation (business process)
    Services/
    Actions/
    Policies/
  Infrastructure/       # Eloquent, integrasi eksternal, storage, API clients
    Persistence/
      Eloquent/
        Models/
        Repositories/
    Services/
    Tenancy/
  Filament/             # UI layer (Resources, Pages, Widgets)
  Support/              # Helpers, Traits, Enums
  Providers/
tests/
  Unit/
  Feature/
docs/
  LARAVEL_BOOST_GUIDE.md
```

> ✳️ **Tujuan utama struktur ini:** memisahkan **contracts/interfaces**, **application logic**, dan **infrastructure concerns** agar perubahan satu lapisan tidak memengaruhi lainnya.

---

## 🧩 2. Layer & Fungsinya

| Layer              | Fungsi                                      | Contoh File                                       |
| ------------------ | ------------------------------------------- | ------------------------------------------------- |
| **Contracts (Domain)** | Interface & kontrak (tanpa framework, tanpa implementasi) | `UserRepositoryInterface`, `UserServiceInterface` |
| **Application**    | Implementasi use-case (orchestrator bisnis) | `UserService`, `CreateUserAction` |
| **Infrastructure** | Integrasi & akses data                      | `EloquentUserRepository`, `AwsS3Service`          |
| **Filament/UI**    | Tampilan admin atau endpoint publik         | `UserResource`, `DashboardWidget`                 |
| **Support**        | Reusable helpers/traits                     | `Trait/HasUuid`, `Helper/DateFormat.php`          |

---

## ⚙️ 3. Pola Utama "Laravel Boost"

Laravel Boost = kombinasi beberapa prinsip:

1. **SOLID** (terutama SRP & DIP)
2. **Clean Architecture** dengan Layered Pattern
3. **Clean Code**
4. **Atomic Action**
5. **Repository Pattern**

---

### 🔸 Repository Pattern

> Semua interaksi database melalui Repository Interface di Contracts layer (app/Domain/) dan implementasi di Infrastructure.

**Interface (Contracts Layer - app/Domain/):**

```php
// app/Domain/Repositories/UserRepositoryInterface.php
namespace App\Domain\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

**Implementation (Infrastructure Layer):**

```php
// app/Infrastructure/Repositories/UserRepository.php
namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private User $model) {}

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }
}
```

**Binding (DomainServiceProvider):**

```php
// app/Providers/DomainServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Domain\Repositories\UserRepositoryInterface::class,
        \App\Infrastructure\Repositories\UserRepository::class
    );
}
```

---

### 🔸 Service Pattern

> Menyimpan **logika bisnis use-case**, memanggil repository & action.

**Interface (Contracts Layer - app/Domain/):**

```php
// app/Domain/Services/UserServiceInterface.php
namespace App\Domain\Services;

use App\Models\User;

interface UserServiceInterface
{
    public function create(array $data, ?array $roles = null): User;
    public function update(int $id, array $data, ?array $roles = null): bool;
}
```

**Implementation (Application Layer):**

```php
// app/Application/Services/UserService.php
namespace App\Application\Services;

use App\Domain\Services\UserServiceInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $repository
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

            // Log activity
            activity()
                ->performedOn($user)
                ->log('User created');

            return $user;
        });
    }
}
```

> 💡 **Note**: Validasi dilakukan di **Form Schema** (Filament) atau **Form Request** (API), bukan di Service layer.

**Binding (DomainServiceProvider):**

```php
public function register(): void
{
    $this->app->bind(
        \App\Domain\Services\UserServiceInterface::class,
        \App\Application\Services\UserService::class
    );
}
```

---

### 🔸 Action Pattern

> Atomic task yang dapat dipanggil oleh Service, Job, atau Controller.

```php
// app/Application/Actions/SendWelcomeEmail.php
namespace App\Application\Actions;

use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function execute(User $user): void
    {
        Mail::to($user->email)->queue(new WelcomeMail($user));
        
        activity()
            ->performedOn($user)
            ->log('Welcome email sent');
    }
}
```

**Usage in Service:**

```php
public function create(array $data, ?array $roles = null): User
{
    return DB::transaction(function () use ($data, $roles) {
        $user = $this->repository->create($data);
        
        // Call action
        app(SendWelcomeEmail::class)->execute($user);
        
        return $user;
    });
}
```

---

### 🔸 Validation Pattern

> Validasi dilakukan di layer UI (Filament Form Schema) atau API (Form Request), bukan di Service layer.

**Filament Form Schema:**

```php
// app/Filament/Resources/Users/Schemas/UserForm.php
Forms\Components\TextInput::make('name')
    ->required()
    ->maxLength(255),

Forms\Components\TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true),

Forms\Components\TextInput::make('password')
    ->password()
    ->required(fn (string $context): bool => $context === 'create')
    ->minLength(8),
```

**API Form Request:**

```php
// app/Http/Requests/CreateUserRequest.php
class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

---

### 🔸 Policy & Permission

> Gunakan `spatie/laravel-permission` + Policy per modul.

```php
// app/Application/Policies/UserPolicy.php
namespace App\Application\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view_user');
    }

    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('update_user') || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete_user') && $user->id !== $model->id;
    }
}
```

---

## 🧾 4. Naming Convention

| Komponen             | Format                           | Contoh                    |
| -------------------- | -------------------------------- | ------------------------- |
| Model                | Singular, PascalCase             | `User`, `AuditLog`        |
| Repository Interface | Singular + `RepositoryInterface` | `UserRepositoryInterface` |
| Repository Impl      | Singular + `Repository`          | `UserRepository`          |
| Service Interface    | Singular + `ServiceInterface`    | `UserServiceInterface`    |
| Service Impl         | Singular + `Service`             | `UserService`             |
| Action               | Verb + Noun                      | `CreateUser`, `SendEmail` |
| Validation           | `FormRequest` atau Form Schema   | `CreateUserRequest`, `UserForm` |
| Policy               | Singular + `Policy`              | `UserPolicy`              |
| Filament Resource    | Singular + `Resource`            | `UserResource`            |

---

## 🔒 5. Aturan Clean Code (Wajib)

| Area                     | Aturan                                                             |
| ------------------------ | ------------------------------------------------------------------ |
| **Controllers/Filament** | Tidak boleh ada query atau logika bisnis. Hanya memanggil Service. |
| **Repositories**         | Tidak boleh validasi bisnis, hanya query.                          |
| **Services**             | Tidak boleh return Response. Kembalikan data mentah yang terstruktur. |
| **Actions**              | Harus atomic, reusable.                                            |
| **Naming**               | Gunakan kata kerja bermakna (create, update, process, approve).    |
| **Logging**              | Gunakan `activity()->causedBy($user)->performedOn($model)->log()`. |
| **Testing**              | Setiap Service dan Action wajib punya unit test minimal satu.      |

---

## 🧠 6. Prinsip Laravel Boost

### ✅ DO (Lakukan)

1. **Transaksi Selalu di Service Layer**
   - Jangan di controller atau action
   - Wrap multi-step operations dengan `DB::transaction()`

2. **Idempotensi untuk proses kritis**
   - Gunakan cache key atau unique constraint
   - Prevent duplicate operations

3. **Repository selalu di-bind via interface**
   - Register di `DomainServiceProvider`
   - Dependency injection via constructor

4. **Gunakan array mentah dengan struktur jelas**
   - Dokumentasikan melalui PHPDoc
   - Validasi di Service/Action sebelum diteruskan

5. **Gunakan Event + Listener untuk side effect**
   - Decouple business logic
   - Async processing

6. **Gunakan Filament Resource sebagai "View Layer"**
   - No business logic in Resources
   - Call Services for operations

7. **Gunakan Queue (Job) untuk proses berat**
   - Email sending
   - File processing
   - External API calls

8. **Gunakan Settings untuk konfigurasi global**
   - `spatie/laravel-settings`
   - Dynamic configuration

### ❌ DON'T (Jangan)

1. ❌ **Business logic di Controller/Resource**
2. ❌ **Direct Eloquent query di Service** (gunakan Repository)
3. ❌ **Framework dependency di Contracts layer (app/Domain/)**
4. ❌ **God classes** (class dengan 500+ lines)
5. ❌ **Magic numbers/strings** (gunakan constants/enums)
6. ❌ **Inline query di Filament** (gunakan Service)
7. ❌ **Commit dengan `dd()` atau `dump()`**

---

## 🔍 7. Integrasi Filament 4 (Best Practice)

### Dependency Injection (DI) di Filament

> Livewire 3 (yang menjadi fondasi Filament 4) **tidak** memanggil constructor milik komponen secara langsung. Jangan menambahkan `__construct()` pada Page/Component karena akan memutus siklus Livewire. Gunakan `boot()` atau action closure dengan type-hint untuk dependency injection.

**Pattern standar yang wajib dipakai:**

```php
// app/Filament/Resources/Units/Pages/CreateUnit.php
class CreateUnit extends CreateRecord
{
    protected UnitServiceInterface $unitService;

    public function boot(UnitServiceInterface $unitService): void
    {
        $this->unitService = $unitService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->unitService->create($data);
    }
}
```

```php
// app/Filament/Resources/Roles/Tables/RolesTable.php
DeleteAction::make()
    ->requiresConfirmation()
    ->action(fn (Role $record, RoleServiceInterface $roleService) => $roleService->delete((int) $record->getKey()));
```

- **Jangan gunakan** helper `app()` di dalam Resource/Page/Table.
- Gunakan **typed closure dependencies** (`fn (Unit $record, UnitServiceInterface $service) => ...`) untuk action yang sudah mendukung.
- Simpan daftar data sementara (misal `permissions`) di properti array dan serahkan ke Service ketika memanggil `handleRecordCreation` / `handleRecordUpdate`.
- Pastikan setiap Resource memiliki `navigationGroup` konsisten agar menu Filament rapi dan mudah dikembangkan.

### Blueprint Modul Filament Baru

Setiap modul (contoh: Units, Roles, Users) wajib mengikuti alur di bawah ini agar kompatibel dengan arsitektur Laravel Boost.

1. **Migrasi Tunggal & Rapi**
   - Gabungkan setup tabel utama + relasi dalam migrasi pertama (`0001_...`) bila masih greenfield.
   - Hindari migrasi tambahan yang langsung menghapus kolom ketika struktur akhirnya sudah diketahui.
2. **Contracts & Services**
   - Tambahkan interface di `app/Domain/Services`.
   - Implementasi bisnis di `app/Application/Services`, gunakan transaksi (`DB::transaction`) untuk operasi multi langkah.
   - Simpan logging aktivitas di Service agar UI tetap tipis.
3. **Repository**
   - Bila memakai database entity baru, lengkapi dengan Repository Interface + implementasi Eloquent.
4. **Filament Resource**
   - `Resource` hanya mengatur schema/table.
   - `Pages` memanggil Service lewat `boot()` + `handleRecord*`.
   - `Tables` & `Actions` memakai dependency injection pada closure.
5. **Testing & Dokumentasi**
   - Tambah minimal 1 feature test per modul (create/update).
   - Update dokumentasi modul ini (bagian ini) setelah fitur stabil.

### Resource Structure

```php
// app/Filament/Resources/Users/UserResource.php
namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('users.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
```

### Best Practices

* ✅ Setiap Resource wajib memanggil Service, bukan langsung query
* ✅ Gunakan **Bulk Actions** dengan `->databaseTransaction()`
* ✅ Gunakan `Policy` untuk akses dan `Filter` untuk data scope
* ✅ Pisahkan UI logic di `Infolist` / `Form` / `Table` builder
* ✅ Custom actions call Service methods

---

## 🧪 8. Testing Guide

### Unit Test (Service Layer)

```php
// tests/Unit/Services/UserServiceTest.php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Application\Services\UserService;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Models\User;
use Mockery;

class UserServiceTest extends TestCase
{
    public function test_register_creates_user(): void
    {
        // Arrange
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Mockery::any(),
            ])
            ->andReturn(new User(['id' => 1, 'name' => 'John Doe']));
            
        $service = new UserService($repository);
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // Act
        $user = $service->register($payload);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
    }
}
```

### Feature Test (Integration)

```php
// tests/Feature/UserRegistrationTest.php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $this->post('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ])
        ->assertSuccessful();
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }
}
```

### Run Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=UserServiceTest

# With coverage
php artisan test --coverage
```

---

## 🧰 9. Tooling Boost

| Tool               | Fungsi                    | Command                           |
| ------------------ | ------------------------- | --------------------------------- |
| **Laravel Pint**   | Code formatting           | `./vendor/bin/pint`               |
| **PHPUnit**        | Testing                   | `php artisan test`                |
| **Laravel Sail**   | Docker environment        | `./vendor/bin/sail up`            |
| **Debugbar**       | Debug development         | Auto-enabled in local             |
| **Laravel Boost**  | AI development assistant  | Integrated with Cursor/Claude     |
| **Blueprint**      | Model generation          | `php artisan blueprint:build`     |
| **Scramble**       | API documentation         | `/docs/api`                       |

---

## 🧾 10. CI/CD Checklist

Sebelum merge ke `main` atau `develop`:

* ✅ Jalankan `./vendor/bin/pint` → formatting
* ✅ Jalankan `php artisan test` → testing
* ✅ Jalankan `npm run build` → build asset
* ✅ Check linter errors
* ✅ Update CHANGELOG.md
* ✅ Update documentation if needed

### GitHub Actions (Automated)

CI pipeline di `.github/workflows/ci.yml` akan otomatis:

1. Run Pint (code style check)
2. Run migrations
3. Build assets
4. Run tests (future)

---

## 🌈 11. Coding Style (PSR + PTSI Standard)

### PSR-12 Compliance

* Use Laravel Pint default configuration
* 4 spaces indentation
* No trailing whitespace
* Use strict types: `declare(strict_types=1);`

### PTSI Specific

* **Font:** Geomanist (sesuai branding)
* **Colors:** Use PTSI brand colors from `AdminPanelProvider`
* **Naming:** Clear, descriptive, no abbreviations
* **Comments:** PHPDoc for public methods
* **Imports:** Organized alphabetically

### Code Example

```php
<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\UserServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * User Service Implementation
 * 
 * Handles user-related business operations.
 */
class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $repository
    ) {}

    /**
     * Register new user.
     *
     * @param array $data
     * @param array|null $roles
     * @return User
     */
    public function create(array $data, ?array $roles = null): User
    {
        return DB::transaction(function () use ($data, $roles) {
            // Implementation
        });
    }
}
```

---

## 🪙 12. Checklist Sebelum Merge

* [ ] Semua test lulus (`php artisan test`)
* [ ] Tidak ada lint error (`./vendor/bin/pint --test`)
* [ ] Service/Repository baru sudah di-bind di `DomainServiceProvider`
* [ ] Tidak ada query di Filament Resource (gunakan Service)
* [ ] Struktur data sudah terdokumentasi jelas (PHPDoc/kontrak service)
* [ ] Audit log aktif untuk aksi utama
* [ ] Dokumentasi README/CHANGELOG diperbarui
* [ ] No `dd()`, `dump()`, or debug code
* [ ] Git commit message follows conventional commits

---

## 🎨 13. PTSI Brand & UI Standards

### Brand Colors

```php
// Already configured in AdminPanelProvider.php
'primary'   => '#184980', // Dark Blue PTSI
'secondary' => '#09A8E1', // Sky Blue
'accent'    => '#00B0A8', // Tosca Green
'warning'   => '#FF8939', // Orange
'success'   => '#16A34A',
'danger'    => '#E11D48',
```

### Font

```css
/* resources/css/app.css */
@font-face {
    font-family: 'Geomanist';
    /* Add font files */
}

body {
    font-family: 'Geomanist', 'Inter', 'ui-sans-serif', 'system-ui', sans-serif;
}
```

### UI Consistency

* Use Filament's built-in components
* Follow Filament 4 design system
* Dark mode enabled by default
* Responsive design required

---

## 🔐 14. Security Best Practices

### Authentication

* Always use Sanctum for API authentication
* Enable 2FA for admin users
* Session timeout: 120 minutes
* Strong password requirements

### Authorization

* Use Policy for all authorization checks
* Use Spatie Permission for role-based access
* Implement field-level permissions if needed
* Log all permission changes

### Audit Trail

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### Data Protection

* Never log sensitive data (passwords, tokens)
* Use encryption for sensitive fields
* Validate all inputs
* Sanitize outputs
* Use CSRF protection

---

## 🚀 15. Performance Optimization

### Database

```php
// ✅ Good: Eager loading
$users = User::with('roles', 'permissions')->get();

// ❌ Bad: N+1 query
$users = User::all();
foreach ($users as $user) {
    $user->roles; // N+1!
}
```

### Caching

```php
use Illuminate\Support\Facades\Cache;

// Cache expensive queries
$users = Cache::remember('users.active', 3600, function () {
    return User::where('active', true)->get();
});
```

### Queue

```php
// ✅ Good: Queue heavy operations
Mail::to($user)->queue(new WelcomeMail($user));

// ❌ Bad: Sync heavy operation
Mail::to($user)->send(new WelcomeMail($user));
```

---

## 📝 16. Module Generation Workflow

### Step 1: Generate Module

```bash
php artisan kit:make-module Order \
  --with-repository \
  --with-service \
  --with-policy \
  --with-filament
```

### Step 2: Create Model & Migration

```bash
php artisan make:model Order -m
```

### Step 3: Implement Repository

Edit `app/Infrastructure/Repositories/OrderRepository.php`:

```php
public function create(array $data)
{
    return Order::create($data);
}
```

### Step 4: Implement Service

Edit `app/Application/Services/OrderService.php`:

```php
public function create(array $data): Order
{
    return DB::transaction(function () use ($data) {
        $order = $this->repository->create($data);
        
        activity()
            ->performedOn($order)
            ->log('Order created');
        
        return $order;
    });
}
```

### Step 5: Register Bindings

Edit `app/Providers/DomainServiceProvider.php`:

```php
$this->app->bind(
    \App\Domain\Repositories\OrderRepositoryInterface::class,
    \App\Infrastructure\Repositories\OrderRepository::class
);

$this->app->bind(
    \App\Domain\Services\OrderServiceInterface::class,
    \App\Application\Services\OrderService::class
);
```

### Step 6: Implement Filament Resource

Edit `app/Filament/Resources/OrderResource.php`:

```php
public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('order_number')->searchable(),
        TextColumn::make('status')->badge(),
        TextColumn::make('created_at')->dateTime(),
    ]);
}
```

### Step 7: Generate Permissions

```bash
php artisan shield:generate --all
```

---

## 🔄 17. Transaction Management

### Database Transaction

```php
use Illuminate\Support\Facades\DB;

public function create(array $data): Order
{
    return DB::transaction(function () use ($data) {
        // All database operations here are atomic
        $order = $this->orderRepository->create($data);
        $invoice = $this->invoiceRepository->createForOrder($order);
        
        // If any exception thrown, all rolled back
        return $order;
    });
}
```

### Pessimistic Locking

```php
// Lock record untuk prevent concurrent updates
$order = Order::where('id', $orderId)
    ->lockForUpdate()
    ->first();
    
$order->status = 'processed';
$order->save();
```

### Idempotency

```php
use Illuminate\Support\Facades\Cache;

public function processPayment(string $orderId): void
{
    $key = "payment:process:{$orderId}";
    
    if (Cache::has($key)) {
        throw new DuplicateOperationException("Payment already processed");
    }
    
    Cache::put($key, true, 600); // 10 minutes lock
    
    try {
        // Process payment
        $this->paymentGateway->charge($orderId);
    } catch (Exception $e) {
        Cache::forget($key);
        throw $e;
    }
}
```

---

## 📊 18. Logging & Monitoring

### Activity Logging

```php
use Spatie\Activitylog\Traits\LogsActivity;

// In Model
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['status', 'amount', 'customer_id'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}

// Manual logging
activity()
    ->causedBy(auth()->user())
    ->performedOn($order)
    ->withProperties(['old_status' => 'pending', 'new_status' => 'approved'])
    ->log('Order approved');
```

### Application Logging

```php
use Illuminate\Support\Facades\Log;

// Info
Log::info('User registered', ['user_id' => $user->id]);

// Warning
Log::warning('API rate limit approached', ['user_id' => $user->id]);

// Error
Log::error('Payment failed', [
    'order_id' => $orderId,
    'error' => $e->getMessage(),
]);
```

---

## 🎯 19. Filament Actions & Forms

### Custom Actions

```php
Action::make('export')
    ->label('Export to Excel')
    ->icon('heroicon-o-arrow-down-tray')
    ->requiresConfirmation()
    ->action(function () {
        return app(ExportServiceInterface::class)->exportUsers();
    })
    ->successNotification(
        Notification::make()
            ->success()
            ->title('Export completed')
    );
```

### Form Validation

```php
TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true)
    ->validationMessages([
        'unique' => 'Email sudah terdaftar.',
    ]),
```

---

## 🏁 20. Deployment Checklist

### Pre-deployment

* [ ] Run full test suite
* [ ] Check code style (`pint`)
* [ ] Update `.env.example`
* [ ] Update version in `CHANGELOG.md`
* [ ] Build assets (`npm run build`)

### Production Setup

```bash
# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# Permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Migrations
php artisan migrate --force

# Queue worker (Supervisor)
# Setup supervisor config for queue:work
```

### Post-deployment

* [ ] Verify all features working
* [ ] Check logs for errors
* [ ] Monitor performance
* [ ] Setup backup schedule
* [ ] Enable monitoring (optional: Sentry, etc)

---

## 📚 21. Additional Resources

### Official Documentation

* [Laravel 12 Docs](https://laravel.com/docs/12.x)
* [Filament 4 Docs](https://filamentphp.com/docs/4.x)
* [Livewire 3 Docs](https://livewire.laravel.com/docs)
* [Spatie Packages](https://spatie.be/open-source)

### PTSI Project Docs

* [README.md](../README.md) - Overview & Quick Start
* [ARCHITECTURE.md](../ARCHITECTURE.md) - Architecture deep dive
* [SETUP.md](../SETUP.md) - Setup instructions
* [FEATURES.md](../FEATURES.md) - Features checklist
* [CONTRIBUTING.md](../CONTRIBUTING.md) - How to contribute

### Learning Resources

* [Laravel Boost Official](https://boost.laravel.com)
* [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
* [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)

---

## 🆘 22. Common Issues & Solutions

### Issue: "Class not found" after generating module

**Solution:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Issue: Permission denied on storage/

**Solution:**
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Issue: Filament assets not loading

**Solution:**
```bash
php artisan filament:upgrade
npm run build
php artisan optimize:clear
```

### Issue: Service binding not working

**Solution:**
Check `app/Providers/DomainServiceProvider.php` is registered in `bootstrap/providers.php`

---

## 📞 23. Support & Contact

**PTSI Digital Team:**

* 📧 Email: ti@ptsi.co.id
* 🐛 Issues: [GitHub Issues](https://github.com/ptsi-digital/laravel-starter-kit-ptsi/issues)
* 📖 Wiki: [Project Wiki](https://github.com/ptsi-digital/laravel-starter-kit-ptsi/wiki)
* 💬 Internal: Slack Channel #ptsi-dev

---

## 📜 24. Revision History

| Version | Date       | Changes                              |
| ------- | ---------- | ------------------------------------ |
| 1.0.0   | 2025-11-06 | Initial Laravel Boost Guidelines     |

---

**✍️ Maintained by:** PTSI Digital Team  
**📅 Last Updated:** November 6, 2025  
**📄 License:** MIT License © 2025 PT Surveyor Indonesia

---

**🚀 Remember: Clean code is not about writing less code, it's about writing code that's easy to understand, maintain, and extend.**

### Relation Manager Pattern (Filament 4)

Gunakan pola konsisten berikut agar Relation Manager tetap tipis dan patuh arsitektur:

- **Schema-based form** – method `form(Schema $schema)` mereturn komponen `Section`, `Grid`, dan `Filament\Forms\Components` untuk menjaga konsistensi tampilan. Hindari memanggil `Form` langsung.
- **Table actions baru di Filament 4** – gunakan `headerActions()` + `recordActions()` dengan kelas aksi `Filament\Actions\CreateAction`, `EditAction`, `DeleteAction`, `ViewAction`. Jangan lagi memakai helper lama `Tables\Actions\...`.
- **Service injection** – resolusi service dilakukan di `boot(ServiceInterface $service)`, kemudian panggil metode domain pada `handleRecordCreation` / `handleRecordUpdate` / `handleRecordDeletion`.
- **Permission checks** – cukup gunakan helper `Auth::user()?->can('resource.permission')` atau policy terkait di method kecil seperti `self::currentUser()` agar mudah diuji.
- **Array typing** – ketika memanipulasi data relasi (misal member IDs, status preset), gunakan `array_map` + `array_filter` dengan anotasi PHPDoc supaya lolos PHPStan level tinggi.

Contoh diterapkan di `Projects` module:

```php
class TicketStatusesRelationManager extends RelationManager
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status Details')
                ->schema([
                    Grid::make(['sm' => 2])->schema([
                        TextInput::make('name')->required(),
                        ColorPicker::make('color')->default('#2563EB'),
                    ]),
                    Toggle::make('is_completed'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()->visible(fn (): bool => self::currentUser()?->can('project-notes.view')),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => self::currentUser()?->can('project-notes.view')),
                DeleteAction::make()->visible(fn (): bool => self::currentUser()?->can('project-notes.view'))->requiresConfirmation(),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->projectService->addStatus($this->resolveProjectId(), $data);
    }
}
```

> 📌 Selalu tulis unit / feature test untuk relation manager baru (misal memanggil Livewire class, memastikan permission bekerja, dan service dipanggil).

