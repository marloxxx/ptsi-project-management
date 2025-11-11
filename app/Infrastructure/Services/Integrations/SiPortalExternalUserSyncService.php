<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Integrations;

use App\Domain\Repositories\UnitRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\ExternalUserSyncServiceInterface;
use App\Domain\Services\UnitServiceInterface;
use App\Domain\Services\UserServiceInterface;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SiPortalExternalUserSyncService implements ExternalUserSyncServiceInterface
{
    /**
     * @param callable(string, array<string, mixed>, string): void|null $progressCallback
     */
    public function __construct(
        private UnitServiceInterface $unitService,
        private UserServiceInterface $userService,
        private UnitRepositoryInterface $units,
        private UserRepositoryInterface $users,
        private HttpFactory $http
    ) {}

    public function sync(?callable $progressCallback = null): array
    {
        $summary = [
            'units' => [
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ],
            'users' => [
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
            ],
            'errors' => [],
        ];

        $summary = $this->syncUnits($summary, $progressCallback);

        return $this->syncUsers($summary, $progressCallback);
    }

    /**
     * @param array{units: array{synced:int, created:int, updated:int, skipped:int}, users: array{synced:int, created:int, updated:int, skipped:int, failed:int}, errors: array<int, array{message:string, context:array<string, mixed>}>} $summary
     * @return array{units: array{synced:int, created:int, updated:int, skipped:int}, users: array{synced:int, created:int, updated:int, skipped:int, failed:int}, errors: array<int, array{message:string, context:array<string, mixed>}>}
     */
    private function syncUnits(array $summary, ?callable $progressCallback): array
    {
        try {
            $remoteUnits = $this->fetchPaginated('/api/units');
        } catch (Throwable $exception) {
            $this->recordError($summary, 'Failed to fetch units from SI Portal.', [
                'exception' => $exception->getMessage(),
            ]);

            Log::error('Failed to fetch units from SI Portal.', [
                'exception' => $exception,
            ]);

            return $summary;
        }

        foreach ($remoteUnits as $payload) {
            $normalized = $this->normalizeUnitPayload($payload);

            if ($normalized === null) {
                $summary['units']['skipped']++;
                $this->notifyProgress($progressCallback, 'unit', $payload, 'skipped');

                continue;
            }

            try {
                $existing = $this->resolveUnit($normalized);

                if ($existing) {
                    $this->unitService->update($existing, $normalized);
                    $summary['units']['updated']++;
                    $this->notifyProgress($progressCallback, 'unit', $normalized, 'updated');
                } else {
                    $this->unitService->create($normalized);
                    $summary['units']['created']++;
                    $this->notifyProgress($progressCallback, 'unit', $normalized, 'created');
                }

                $summary['units']['synced']++;
            } catch (Throwable $exception) {
                $summary['units']['skipped']++;
                $this->recordError($summary, 'Failed to sync unit.', [
                    'payload' => $normalized,
                    'exception' => $exception->getMessage(),
                ]);
                Log::error('Failed to sync unit from SI Portal.', [
                    'payload' => $normalized,
                    'exception' => $exception,
                ]);
                $this->notifyProgress($progressCallback, 'unit', $normalized, 'failed');
            }
        }

        return $summary;
    }

    /**
     * @param array{units: array{synced:int, created:int, updated:int, skipped:int}, users: array{synced:int, created:int, updated:int, skipped:int, failed:int}, errors: array<int, array{message:string, context:array<string, mixed>}>} $summary
     * @return array{units: array{synced:int, created:int, updated:int, skipped:int}, users: array{synced:int, created:int, updated:int, skipped:int, failed:int}, errors: array<int, array{message:string, context:array<string, mixed>}>}
     */
    private function syncUsers(array $summary, ?callable $progressCallback): array
    {
        try {
            $remoteUsers = $this->fetchPaginated('/api/users');
        } catch (Throwable $exception) {
            $this->recordError($summary, 'Failed to fetch users from SI Portal.', [
                'exception' => $exception->getMessage(),
            ]);

            Log::error('Failed to fetch users from SI Portal.', [
                'exception' => $exception,
            ]);

            return $summary;
        }

        foreach ($remoteUsers as $payload) {
            $normalized = $this->normalizeUserPayload($payload);

            if ($normalized === null) {
                $summary['users']['skipped']++;
                $this->notifyProgress($progressCallback, 'user', $payload, 'skipped');

                continue;
            }

            $attributes = $normalized['attributes'];
            $context = $normalized['context'];

            try {
                $existing = $this->resolveUser($attributes);

                if ($existing) {
                    $updatePayload = $attributes;
                    unset($updatePayload['password']);

                    $this->userService->update($existing->id, $updatePayload);
                    $summary['users']['updated']++;
                    $this->notifyProgress($progressCallback, 'user', $context, 'updated');
                } else {
                    $attributes['password'] = $attributes['password'] ?? Str::password(16);

                    $this->userService->create($attributes, ['user']);
                    $summary['users']['created']++;
                    $this->notifyProgress($progressCallback, 'user', $context, 'created');
                }

                $summary['users']['synced']++;
            } catch (Throwable $exception) {
                $summary['users']['failed']++;
                $this->recordError($summary, 'Failed to sync user.', [
                    'payload' => $context,
                    'exception' => $exception->getMessage(),
                ]);

                Log::error('Failed to sync user from SI Portal.', [
                    'payload' => $context,
                    'exception' => $exception,
                ]);

                $this->notifyProgress($progressCallback, 'user', $context, 'failed');
            }
        }

        return $summary;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchPaginated(string $path, int $perPage = 100): Collection
    {
        $baseUrl = rtrim((string) config('services.siportal.base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('SI Portal base URL is not configured.');
        }

        $items = collect();
        $page = 1;

        do {
            $response = $this->request(
                "{$baseUrl}{$path}",
                [
                    'per_page' => $perPage,
                    'page' => $page,
                ]
            );

            $payload = $response->json();

            $data = data_get($payload, 'data', []);

            if (is_array($data)) {
                $items = $items->merge($data);
            }

            $meta = data_get($payload, 'meta', []);
            $lastPage = (int) data_get($meta, 'last_page', $page);

            if ($lastPage < 1) {
                $lastPage = 1;
            }

            $page++;
        } while ($page <= $lastPage);

        return $items
            ->filter(fn($item): bool => is_array($item))
            ->values()
            ->map(fn(array $item): array => $item);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function request(string $url, array $query = []): Response
    {
        try {
            $response = $this->http
                ->acceptJson()
                ->withToken((string) config('services.siportal.token'))
                ->timeout((float) config('services.siportal.timeout', 10))
                ->get($url, $query);

            $response->throw();

            return $response;
        } catch (RequestException $exception) {
            throw new RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{name:string, code:string, sinav_unit_id:?string, status:string}|null
     */
    private function normalizeUnitPayload(array $payload): ?array
    {
        $name = trim((string) (Arr::get($payload, 'name') ?? Arr::get($payload, 'nama_unit') ?? ''));
        $code = strtoupper(trim((string) (Arr::get($payload, 'code') ?? Arr::get($payload, 'kode_unit') ?? '')));

        if ($name === '' || $code === '') {
            return null;
        }

        $sinavUnitId = Arr::get($payload, 'sinav_unit_id') ?? Arr::get($payload, 'id_unit');
        $status = strtolower((string) (Arr::get($payload, 'status', 'active')));

        return [
            'name' => $name,
            'code' => $code,
            'sinav_unit_id' => $sinavUnitId ? (string) $sinavUnitId : null,
            'status' => $status === 'inactive' ? 'inactive' : 'active',
        ];
    }

    /**
     * @param array{name:string, code:string, sinav_unit_id:?string, status:string} $payload
     */
    private function resolveUnit(array $payload): ?Unit
    {
        if (! empty($payload['sinav_unit_id'])) {
            $unit = $this->units->findBySinavId($payload['sinav_unit_id']);

            if ($unit) {
                return $unit;
            }
        }

        return $this->units->findByCode($payload['code']);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{attributes: array<string, mixed>, context: array<string, mixed>}|null
     */
    private function normalizeUserPayload(array $payload): ?array
    {
        $email = strtolower(trim((string) (Arr::get($payload, 'email') ?? Arr::get($payload, 'user_email') ?? '')));
        $nik = trim((string) (Arr::get($payload, 'nik') ?? Arr::get($payload, 'employee_number') ?? ''));

        if ($email === '' && $nik === '') {
            return null;
        }

        $unit = $this->resolveUserUnit($payload);

        $fullName = $this->normalizeString(Arr::get($payload, 'full_name') ?? Arr::get($payload, 'name'));
        $name = $this->normalizeString(Arr::get($payload, 'name') ?? Arr::get($payload, 'full_name'));

        if ($name === null && $fullName !== null) {
            $name = $fullName;
        }

        if ($name === null) {
            $name = $email !== '' ? $email : $nik;
        }

        $status = strtolower($this->normalizeString(Arr::get($payload, 'status')) ?? 'active');

        $attributes = [
            'name' => $name,
            'full_name' => $fullName,
            'username' => $this->normalizeString(Arr::get($payload, 'username') ?? Arr::get($payload, 'user_name')),
            'email' => $email !== '' ? $email : null,
            'nik' => $nik !== '' ? $nik : null,
            'phone' => $this->normalizeString(
                Arr::get($payload, 'phone')
                    ?? Arr::get($payload, 'mobile')
                    ?? Arr::get($payload, 'mobile_phone')
                    ?? Arr::get($payload, 'handphone')
            ),
            'avatar' => $this->normalizeString(
                Arr::get($payload, 'avatar')
                    ?? Arr::get($payload, 'photo')
                    ?? Arr::get($payload, 'picture')
            ),
            'employee_status' => $this->normalizeString(Arr::get($payload, 'employee_status')),
            'position' => $this->normalizeString(Arr::get($payload, 'position')),
            'position_level' => $this->normalizeString(Arr::get($payload, 'position_level')),
            'gender' => $this->normalizeGender(Arr::get($payload, 'gender')),
            'status' => $status === 'inactive' ? 'inactive' : 'active',
            'unit_id' => $unit?->id,
        ];

        $context = array_filter([
            'email' => $attributes['email'],
            'nik' => $attributes['nik'],
            'unit_code' => $this->normalizeString(
                Arr::get($payload, 'unit.code')
                    ?? Arr::get($payload, 'unit.kode_unit')
                    ?? Arr::get($payload, 'unit_code')
            ),
            'unit_name' => $this->normalizeString(
                Arr::get($payload, 'unit.name')
                    ?? Arr::get($payload, 'unit.nama_unit')
                    ?? Arr::get($payload, 'unit_name')
            ),
        ], static fn($value): bool => $value !== null);

        return [
            'attributes' => $attributes,
            'context' => $context,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveUserUnit(array $payload): ?Unit
    {
        $sinavUnitId = Arr::get($payload, 'unit.sinav_unit_id')
            ?? Arr::get($payload, 'unit.id_unit')
            ?? Arr::get($payload, 'sinav_unit_id')
            ?? Arr::get($payload, 'unit_id');

        if ($sinavUnitId) {
            $unit = $this->units->findBySinavId((string) $sinavUnitId);

            if ($unit) {
                return $unit;
            }
        }

        $unitCode = Arr::get($payload, 'unit.code')
            ?? Arr::get($payload, 'unit.kode_unit')
            ?? Arr::get($payload, 'unit_code');

        if ($unitCode) {
            return $this->units->findByCode(strtoupper(trim((string) $unitCode)));
        }

        return null;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function resolveUser(array $attributes): ?User
    {
        if (! empty($attributes['nik'])) {
            $user = $this->users->findByNik((string) $attributes['nik']);

            if ($user) {
                return $user;
            }
        }

        if (! empty($attributes['email'])) {
            return $this->users->findByEmail((string) $attributes['email']);
        }

        return null;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function normalizeGender(mixed $gender): ?string
    {
        $value = strtolower((string) ($gender ?? ''));

        return match ($value) {
            'male', 'm', 'laki-laki', 'l' => 'male',
            'female', 'f', 'perempuan', 'p' => 'female',
            default => null,
        };
    }

    /**
     * @param array{units: array{synced:int, created:int, updated:int, skipped:int}, users: array{synced:int, created:int, updated:int, skipped:int, failed:int}, errors: array<int, array{message:string, context:array<string, mixed>}>} &$summary
     * @param array<string, mixed> $context
     */
    private function recordError(array &$summary, string $message, array $context = []): void
    {
        $summary['errors'][] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function notifyProgress(?callable $callback, string $entity, array $payload, string $status): void
    {
        if ($callback) {
            $callback($entity, $payload, $status);
        }
    }
}
