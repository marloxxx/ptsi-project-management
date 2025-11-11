<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kit:make-module
                            {name : The name of the module}
                            {--with-repository : Generate repository interface and implementation}
                            {--with-service : Generate service interface and implementation}
                            {--with-policy : Generate policy class}
                            {--with-filament : Generate Filament resource}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a complete module with clean architecture structure';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $moduleName = Str::studly($name);

        $this->info("🚀 Generating module: {$moduleName}");

        // Generate based on options
        if ($this->option('with-repository')) {
            $this->generateRepository($moduleName);
        }

        if ($this->option('with-service')) {
            $this->generateService($moduleName);
        }

        if ($this->option('with-policy')) {
            $this->generatePolicy($moduleName);
        }

        if ($this->option('with-filament')) {
            $this->generateFilamentResource($moduleName);
        }

        $this->newLine();
        $this->info("✅ Module {$moduleName} generated successfully!");
        $this->newLine();
        $this->comment("Don't forget to bind interfaces in app/Providers/DomainServiceProvider.php");

        return Command::SUCCESS;
    }

    protected function generateRepository(string $moduleName): void
    {
        $this->info('  → Creating Repository Interface and Implementation...');

        // Interface
        $interfacePath = app_path("Domain/Repositories/{$moduleName}RepositoryInterface.php");
        $this->ensureDirectoryExists(dirname($interfacePath));
        $this->files->put($interfacePath, $this->getRepositoryInterfaceStub($moduleName));

        // Implementation
        $implPath = app_path("Infrastructure/Repositories/{$moduleName}Repository.php");
        $this->ensureDirectoryExists(dirname($implPath));
        $this->files->put($implPath, $this->getRepositoryImplementationStub($moduleName));

        $this->line('    ✓ Repository created');
    }

    protected function generateService(string $moduleName): void
    {
        $this->info('  → Creating Service Interface and Implementation...');

        // Interface
        $interfacePath = app_path("Domain/Services/{$moduleName}ServiceInterface.php");
        $this->ensureDirectoryExists(dirname($interfacePath));
        $this->files->put($interfacePath, $this->getServiceInterfaceStub($moduleName));

        // Implementation
        $implPath = app_path("Application/Services/{$moduleName}Service.php");
        $this->ensureDirectoryExists(dirname($implPath));
        $this->files->put($implPath, $this->getServiceImplementationStub($moduleName));

        $this->line('    ✓ Service created');
    }

    protected function generatePolicy(string $moduleName): void
    {
        $this->info('  → Creating Policy...');

        $policyPath = app_path("Application/Policies/{$moduleName}Policy.php");
        $this->ensureDirectoryExists(dirname($policyPath));
        $this->files->put($policyPath, $this->getPolicyStub($moduleName));

        $this->line('    ✓ Policy created');
    }

    protected function generateFilamentResource(string $moduleName): void
    {
        $this->info('  → Creating Filament Resource...');

        $this->call('make:filament-resource', [
            'name' => $moduleName,
            '--generate' => false,
        ]);

        $this->line('    ✓ Filament Resource created');
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    protected function getRepositoryInterfaceStub(string $moduleName): string
    {
        return <<<PHP
<?php

namespace App\Domain\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface {$moduleName}RepositoryInterface
{
    /**
     * Get all {$moduleName} records.
     */
    public function all(): Collection;

    /**
     * Find {$moduleName} by ID.
     */
    public function find(int \$id);

    /**
     * Create new {$moduleName}.
     */
    public function create(array \$data);

    /**
     * Update {$moduleName}.
     */
    public function update(int \$id, array \$data): bool;

    /**
     * Delete {$moduleName}.
     */
    public function delete(int \$id): bool;
}

PHP;
    }

    protected function getRepositoryImplementationStub(string $moduleName): string
    {
        return <<<PHP
<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\\{$moduleName}RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class {$moduleName}Repository implements {$moduleName}RepositoryInterface
{
    /**
     * Get all {$moduleName} records.
     */
    public function all(): Collection
    {
        // TODO: Implement with your Model
        return collect();
    }

    /**
     * Find {$moduleName} by ID.
     */
    public function find(int \$id)
    {
        // TODO: Implement with your Model
        return null;
    }

    /**
     * Create new {$moduleName}.
     */
    public function create(array \$data)
    {
        // TODO: Implement with your Model
        return null;
    }

    /**
     * Update {$moduleName}.
     */
    public function update(int \$id, array \$data): bool
    {
        // TODO: Implement with your Model
        return false;
    }

    /**
     * Delete {$moduleName}.
     */
    public function delete(int \$id): bool
    {
        // TODO: Implement with your Model
        return false;
    }
}

PHP;
    }

    protected function getServiceInterfaceStub(string $moduleName): string
    {
        $modelVar = Str::studly($moduleName);

        return <<<PHP
<?php

namespace App\Domain\Services;

use Illuminate\Database\Eloquent\Collection;

interface {$moduleName}ServiceInterface
{
    /**
     * Get all {$moduleName} records.
     */
    public function all(): Collection;

    /**
     * Find {$moduleName} by ID.
     */
    public function find(int \$id);

    /**
     * Create new {$moduleName}.
     */
    public function create(array \$data);

    /**
     * Update {$moduleName}.
     */
    public function update(int \$id, array \$data): bool;

    /**
     * Delete {$moduleName}.
     */
    public function delete(int \$id): bool;
}

PHP;
    }

    protected function getServiceImplementationStub(string $moduleName): string
    {
        return <<<PHP
<?php

namespace App\Application\Services;

use App\Domain\Services\\{$moduleName}ServiceInterface;
use App\Domain\Repositories\\{$moduleName}RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class {$moduleName}Service implements {$moduleName}ServiceInterface
{
    public function __construct(
        protected {$moduleName}RepositoryInterface \$repository
    ) {}

    /**
     * Get all {$moduleName} records.
     */
    public function all(): Collection
    {
        return \$this->repository->all();
    }

    /**
     * Find {$moduleName} by ID.
     */
    public function find(int \$id)
    {
        return \$this->repository->find(\$id);
    }

    /**
     * Create new {$moduleName}.
     */
    public function create(array \$data)
    {
        return DB::transaction(function () use (\$data) {
            \$record = \$this->repository->create(\$data);

            // Log activity
            activity()
                ->performedOn(\$record)
                ->event('created')
                ->log('{$moduleName} created');

            return \$record;
        });
    }

    /**
     * Update {$moduleName}.
     */
    public function update(int \$id, array \$data): bool
    {
        return DB::transaction(function () use (\$id, \$data) {
            \$record = \$this->repository->find(\$id);

            if (! \$record) {
                return false;
            }

            \$this->repository->update(\$id, \$data);

            // Log activity
            activity()
                ->performedOn(\$record)
                ->event('updated')
                ->log('{$moduleName} updated');

            return true;
        });
    }

    /**
     * Delete {$moduleName}.
     */
    public function delete(int \$id): bool
    {
        return DB::transaction(function () use (\$id) {
            \$record = \$this->repository->find(\$id);

            if (! \$record) {
                return false;
            }

            // Log activity before deletion
            activity()
                ->performedOn(\$record)
                ->event('deleted')
                ->log('{$moduleName} deleted');

            return \$this->repository->delete(\$id);
        });
    }
}

PHP;
    }

    protected function getPolicyStub(string $moduleName): string
    {
        $modelVar = Str::camel($moduleName);

        return <<<PHP
<?php

namespace App\Application\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class {$moduleName}Policy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User \$user): bool
    {
        return \$user->can('view_{$modelVar}');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User \$user, \$model): bool
    {
        return \$user->can('view_{$modelVar}');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User \$user): bool
    {
        return \$user->can('create_{$modelVar}');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User \$user, \$model): bool
    {
        return \$user->can('update_{$modelVar}');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User \$user, \$model): bool
    {
        return \$user->can('delete_{$modelVar}');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User \$user, \$model): bool
    {
        return \$user->can('restore_{$modelVar}');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User \$user, \$model): bool
    {
        return \$user->can('force_delete_{$modelVar}');
    }
}

PHP;
    }
}
