<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\ProjectCustomFieldRepositoryInterface;
use App\Domain\Repositories\TicketCustomValueRepositoryInterface;
use App\Domain\Services\CustomFieldServiceInterface;
use App\Models\ProjectCustomField;
use App\Models\Ticket;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomFieldService implements CustomFieldServiceInterface
{
    public function __construct(
        protected ProjectCustomFieldRepositoryInterface $customFieldRepository,
        protected TicketCustomValueRepositoryInterface $customValueRepository
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createField(int $projectId, array $data): ProjectCustomField
    {
        return DB::transaction(function () use ($projectId, $data) {
            $data['project_id'] = $projectId;

            // Ensure key is unique for the project
            if (isset($data['key'])) {
                $existing = $this->customFieldRepository->findByKey($projectId, $data['key']);
                if ($existing) {
                    throw new \RuntimeException("Custom field with key '{$data['key']}' already exists for this project.");
                }
            }

            $field = $this->customFieldRepository->create($data);

            activity()
                ->performedOn($field)
                ->event('created')
                ->log('Custom field created');

            return $field;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateField(int $fieldId, array $data): ProjectCustomField
    {
        return DB::transaction(function () use ($fieldId, $data) {
            $field = $this->customFieldRepository->find($fieldId);
            if (! $field) {
                throw new \RuntimeException("Custom field with ID {$fieldId} not found.");
            }

            // Ensure key is unique for the project if being changed
            if (isset($data['key']) && $data['key'] !== $field->key) {
                $existing = $this->customFieldRepository->findByKey($field->project_id, $data['key']);
                if ($existing) {
                    throw new \RuntimeException("Custom field with key '{$data['key']}' already exists for this project.");
                }
            }

            $updated = $this->customFieldRepository->update($field, $data);

            activity()
                ->performedOn($updated)
                ->event('updated')
                ->log('Custom field updated');

            return $updated;
        });
    }

    public function deleteField(int $fieldId): bool
    {
        return DB::transaction(function () use ($fieldId) {
            $field = $this->customFieldRepository->find($fieldId);
            if (! $field) {
                throw new \RuntimeException("Custom field with ID {$fieldId} not found.");
            }

            activity()
                ->performedOn($field)
                ->event('deleted')
                ->log('Custom field deleted');

            return $this->customFieldRepository->delete($field);
        });
    }

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function getFieldsForProject(int $projectId): Collection
    {
        return $this->customFieldRepository->forProject($projectId);
    }

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function getActiveFieldsForProject(int $projectId): Collection
    {
        return $this->customFieldRepository->activeForProject($projectId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $values
     */
    public function syncCustomValuesForTicket(Ticket $ticket, array $values): void
    {
        DB::transaction(function () use ($ticket, $values) {
            $this->customValueRepository->syncForTicket($ticket, $values);

            activity()
                ->performedOn($ticket)
                ->event('updated')
                ->log('Custom field values synced');
        });
    }

    /**
     * Generate form schema components for custom fields.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public function generateFormSchemaForProject(int $projectId): array
    {
        $fields = $this->getActiveFieldsForProject($projectId);
        /** @var array<int, \Filament\Schemas\Components\Component> $components */
        $components = [];

        foreach ($fields as $field) {
            $component = $this->createFormComponent($field);
            if ($component !== null) {
                $components[] = $component;
            }
        }

        return $components;
    }

    /**
     * Create a form component based on field type.
     */
    protected function createFormComponent(ProjectCustomField $field): ?Component
    {
        $fieldName = "custom_fields.{$field->id}";

        $component = match ($field->type) {
            'text' => TextInput::make($fieldName)
                ->label($field->label)
                ->required($field->required)
                ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? $state : null),

            'number' => TextInput::make($fieldName)
                ->label($field->label)
                ->numeric()
                ->required($field->required)
                ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (float) $state : null),

            'select' => Select::make($fieldName)
                ->label($field->label)
                ->options($field->options ?? [])
                ->required($field->required)
                ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? $state : null),

            'date' => DatePicker::make($fieldName)
                ->label($field->label)
                ->required($field->required)
                ->native(false)
                ->dehydrateStateUsing(fn ($state) => $state !== null ? $state : null),

            default => null,
        };

        return $component;
    }
}
