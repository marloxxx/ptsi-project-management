<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorityMap = $this->ensureTicketPriorities();
        $userMap = $this->prepareUsers();

        $projects = collect($this->demoProjects());

        foreach ($projects as $projectData) {
            if (Project::query()->where('ticket_prefix', $projectData['ticket_prefix'])->exists()) {
                continue;
            }

            /** @var Project $project */
            $project = Project::query()->create([
                'name' => $projectData['name'],
                'description' => $projectData['description'],
                'ticket_prefix' => $projectData['ticket_prefix'],
                'color' => $projectData['color'],
                'start_date' => $projectData['start_date'],
                'end_date' => $projectData['end_date'],
                'pinned_at' => $projectData['pinned_at'],
            ]);

            $memberIds = collect($projectData['members'])
                ->map(fn (string $email): ?int => $userMap[$email] ?? null)
                ->filter()
                ->values()
                ->all();

            if ($memberIds !== []) {
                $project->members()->syncWithoutDetaching($memberIds);
            }

            $statusMap = $this->seedStatuses($project, $projectData['statuses']);
            $epicMap = $this->seedEpics($project, $projectData['epics']);

            $this->seedTickets(
                project: $project,
                tickets: $projectData['tickets'],
                statusMap: $statusMap,
                epicMap: $epicMap,
                priorityMap: $priorityMap,
                userMap: $userMap,
                fallbackUserId: $memberIds[0] ?? null,
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function prepareUsers(): array
    {
        $demoUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@ptsi.co.id',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin PTSI',
                'email' => 'admin.test@ptsi.co.id',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager PTSI',
                'email' => 'manager@ptsi.co.id',
                'role' => 'manager',
            ],
            [
                'name' => 'Product Owner',
                'email' => 'product.owner@ptsi.co.id',
                'role' => 'manager',
            ],
            [
                'name' => 'Development Lead',
                'email' => 'dev.lead@ptsi.co.id',
                'role' => 'staff',
            ],
            [
                'name' => 'QA Lead',
                'email' => 'qa.lead@ptsi.co.id',
                'role' => 'staff',
            ],
            [
                'name' => 'Operations Manager',
                'email' => 'ops.manager@ptsi.co.id',
                'role' => 'manager',
            ],
            [
                'name' => 'Business Analyst',
                'email' => 'business.analyst@ptsi.co.id',
                'role' => 'staff',
            ],
        ];

        $map = [];

        foreach ($demoUsers as $userData) {
            /** @var User $user */
            $user = User::query()->firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            $map[$userData['email']] = (int) $user->getKey();
        }

        return $map;
    }

    /**
     * @return Collection<string, TicketPriority>
     */
    private function ensureTicketPriorities(): Collection
    {
        $priorities = TicketPriority::query()
            ->get()
            ->keyBy(fn (TicketPriority $priority): string => strtolower($priority->name));

        if ($priorities->isEmpty()) {
            $this->call(TicketPrioritySeeder::class);

            $priorities = TicketPriority::query()
                ->get()
                ->keyBy(fn (TicketPriority $priority): string => strtolower($priority->name));
        }

        return $priorities;
    }

    /**
     * @param  array<int, array<string, mixed>>  $statuses
     * @return array<string, TicketStatus>
     */
    private function seedStatuses(Project $project, array $statuses): array
    {
        $map = [];

        foreach ($statuses as $index => $statusData) {
            /** @var TicketStatus $status */
            $status = $project->ticketStatuses()->create([
                'name' => $statusData['name'],
                'color' => $statusData['color'],
                'is_completed' => $statusData['is_completed'] ?? false,
                'sort_order' => $index,
            ]);

            $map[$statusData['name']] = $status;
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $epics
     * @return array<string, Epic>
     */
    private function seedEpics(Project $project, array $epics): array
    {
        $map = [];

        foreach ($epics as $epicData) {
            /** @var Epic $epic */
            $epic = $project->epics()->create([
                'name' => $epicData['name'],
                'description' => $epicData['description'],
                'start_date' => $epicData['start_date'],
                'end_date' => $epicData['end_date'],
                'sort_order' => $epicData['sort_order'] ?? null,
            ]);

            $map[$epicData['name']] = $epic;
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     * @param  array<string, TicketStatus>  $statusMap
     * @param  array<string, Epic>  $epicMap
     */
    private function seedTickets(
        Project $project,
        array $tickets,
        array $statusMap,
        array $epicMap,
        Collection $priorityMap,
        array $userMap,
        ?int $fallbackUserId,
    ): void {
        foreach ($tickets as $ticketData) {
            $status = $statusMap[$ticketData['status']] ?? reset($statusMap);

            if (! $status instanceof TicketStatus) {
                continue;
            }

            $priorityKey = strtolower($ticketData['priority'] ?? 'medium');
            $priority = $priorityMap->get($priorityKey) ?? $priorityMap->first();

            /** @var Epic|null $epic */
            $epic = null;

            if (isset($ticketData['epic'])) {
                $epic = $epicMap[$ticketData['epic']] ?? null;
            }

            /** @var Ticket $ticket */
            $ticket = $project->tickets()->create([
                'ticket_status_id' => $status->getKey(),
                'priority_id' => $priority?->getKey(),
                'epic_id' => $epic?->getKey(),
                'uuid' => $ticketData['uuid'] ?? $this->generateTicketUuid($project),
                'name' => $ticketData['name'],
                'description' => $ticketData['description'],
                'start_date' => $ticketData['start_date'],
                'due_date' => $ticketData['due_date'],
                'created_by' => $userMap[$ticketData['created_by']] ?? $fallbackUserId,
            ]);

            $assigneeIds = collect($ticketData['assignees'] ?? [])
                ->map(fn (string $email): ?int => $userMap[$email] ?? null)
                ->filter()
                ->values()
                ->all();

            if ($assigneeIds !== []) {
                $ticket->assignees()->syncWithoutDetaching($assigneeIds);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoProjects(): array
    {
        $now = Carbon::now();

        return [
            [
                'name' => 'Digital Transformation 2025',
                'description' => 'Program utama untuk menyatukan portal internal PTSI dan mengotomasi alur kerja lintas unit.',
                'ticket_prefix' => 'DGT',
                'color' => '#184980',
                'start_date' => $now->copy()->subWeeks(3),
                'end_date' => $now->copy()->addMonths(3),
                'pinned_at' => $now->copy()->subDay(),
                'members' => [
                    'admin@ptsi.co.id',
                    'manager@ptsi.co.id',
                    'product.owner@ptsi.co.id',
                    'dev.lead@ptsi.co.id',
                    'qa.lead@ptsi.co.id',
                ],
                'statuses' => [
                    ['name' => 'Backlog', 'color' => '#94A3B8'],
                    ['name' => 'Todo', 'color' => '#0EA5E9'],
                    ['name' => 'In Progress', 'color' => '#F97316'],
                    ['name' => 'Review', 'color' => '#8B5CF6'],
                    ['name' => 'Done', 'color' => '#10B981', 'is_completed' => true],
                ],
                'epics' => [
                    [
                        'name' => 'Employee Onboarding Portal',
                        'description' => 'Portal onboarding karyawan baru lengkap dengan approval otomatis.',
                        'start_date' => $now->copy()->subWeeks(2),
                        'end_date' => $now->copy()->addWeeks(6),
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Analytics Dashboard',
                        'description' => 'Dashboard KPI proyek dan pemantauan SLA unit.',
                        'start_date' => $now->copy()->subWeek(),
                        'end_date' => $now->copy()->addWeeks(8),
                        'sort_order' => 2,
                    ],
                ],
                'tickets' => [
                    [
                        'name' => 'Integrasi SSO dengan Azure AD',
                        'description' => 'Aktifkan login tunggal untuk portal onboarding dan gunakan MFA bawaan.',
                        'status' => 'In Progress',
                        'priority' => 'High',
                        'epic' => 'Employee Onboarding Portal',
                        'start_date' => $now->copy()->subDays(7),
                        'due_date' => $now->copy()->addDays(5),
                        'created_by' => 'admin@ptsi.co.id',
                        'assignees' => [
                            'dev.lead@ptsi.co.id',
                            'product.owner@ptsi.co.id',
                        ],
                    ],
                    [
                        'name' => 'Rancang modul tugas onboarding',
                        'description' => 'Checklist otomatis untuk HR dan atasan langsung ketika karyawan baru join.',
                        'status' => 'Todo',
                        'priority' => 'Medium',
                        'epic' => 'Employee Onboarding Portal',
                        'start_date' => $now->copy()->subDays(2),
                        'due_date' => $now->copy()->addDays(10),
                        'created_by' => 'manager@ptsi.co.id',
                        'assignees' => [
                            'manager@ptsi.co.id',
                            'business.analyst@ptsi.co.id',
                        ],
                    ],
                    [
                        'name' => 'Widget tren tiket realtime',
                        'description' => 'Tambahkan widget Livewire yang menampilkan tren backlog 7 hari terakhir.',
                        'status' => 'Review',
                        'priority' => 'Low',
                        'epic' => 'Analytics Dashboard',
                        'start_date' => $now->copy()->subDays(4),
                        'due_date' => $now->copy()->addDays(2),
                        'created_by' => 'product.owner@ptsi.co.id',
                        'assignees' => [
                            'qa.lead@ptsi.co.id',
                            'dev.lead@ptsi.co.id',
                        ],
                    ],
                    [
                        'name' => 'Studi kebutuhan dashboard SLA unit',
                        'description' => 'Kumpulkan indikator utama dari setiap unit untuk divisualisasikan dalam dashboard.',
                        'status' => 'Backlog',
                        'priority' => 'Low',
                        'epic' => 'Analytics Dashboard',
                        'start_date' => $now->copy()->addDays(3),
                        'due_date' => $now->copy()->addWeeks(3),
                        'created_by' => 'manager@ptsi.co.id',
                        'assignees' => [
                            'business.analyst@ptsi.co.id',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Operational Excellence Platform',
                'description' => 'Inisiatif untuk meningkatkan kolaborasi lapangan dan monitoring SLA operasional.',
                'ticket_prefix' => 'OPS',
                'color' => '#00B0A8',
                'start_date' => $now->copy()->subMonth(),
                'end_date' => $now->copy()->addMonths(2),
                'pinned_at' => null,
                'members' => [
                    'admin.test@ptsi.co.id',
                    'ops.manager@ptsi.co.id',
                    'business.analyst@ptsi.co.id',
                    'qa.lead@ptsi.co.id',
                ],
                'statuses' => [
                    ['name' => 'Backlog', 'color' => '#94A3B8'],
                    ['name' => 'Ready', 'color' => '#22D3EE'],
                    ['name' => 'In Progress', 'color' => '#F97316'],
                    ['name' => 'Blocked', 'color' => '#F43F5E'],
                    ['name' => 'Done', 'color' => '#10B981', 'is_completed' => true],
                ],
                'epics' => [
                    [
                        'name' => 'Mobile Inspection Suite',
                        'description' => 'Aplikasi mobile untuk inspeksi lapangan dengan sinkronisasi realtime.',
                        'start_date' => $now->copy()->subWeeks(4),
                        'end_date' => $now->copy()->addWeeks(4),
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Service Reliability Monitoring',
                        'description' => 'Monitoring SLA otomatis dengan notifikasi eskalasi.',
                        'start_date' => $now->copy()->subWeeks(1),
                        'end_date' => $now->copy()->addWeeks(5),
                        'sort_order' => 2,
                    ],
                ],
                'tickets' => [
                    [
                        'name' => 'Rilis beta aplikasi inspeksi',
                        'description' => 'Siapkan build beta untuk tim lapangan dan dokumentasi changelog.',
                        'status' => 'Ready',
                        'priority' => 'High',
                        'epic' => 'Mobile Inspection Suite',
                        'start_date' => $now->copy()->subDays(3),
                        'due_date' => $now->copy()->addDays(4),
                        'created_by' => 'ops.manager@ptsi.co.id',
                        'assignees' => [
                            'dev.lead@ptsi.co.id',
                            'qa.lead@ptsi.co.id',
                        ],
                    ],
                    [
                        'name' => 'Siapkan skrip pengujian lapangan',
                        'description' => 'Checklist pengujian offline/online sebelum rollout mobile app.',
                        'status' => 'In Progress',
                        'priority' => 'Medium',
                        'epic' => 'Mobile Inspection Suite',
                        'start_date' => $now->copy()->subDays(1),
                        'due_date' => $now->copy()->addDays(6),
                        'created_by' => 'ops.manager@ptsi.co.id',
                        'assignees' => [
                            'qa.lead@ptsi.co.id',
                        ],
                    ],
                    [
                        'name' => 'Implementasi notifikasi eskalasi SLA',
                        'description' => 'Kirim email + notifikasi in-app bila SLA melewati ambang batas.',
                        'status' => 'Blocked',
                        'priority' => 'Critical',
                        'epic' => 'Service Reliability Monitoring',
                        'start_date' => $now->copy()->subDays(5),
                        'due_date' => $now->copy()->addDays(1),
                        'created_by' => 'admin.test@ptsi.co.id',
                        'assignees' => [
                            'business.analyst@ptsi.co.id',
                        ],
                    ],
                    [
                        'name' => 'Dashboard pemantauan SLA cabang',
                        'description' => 'Visualisasi waktu respon dan penyelesaian untuk seluruh cabang.',
                        'status' => 'Backlog',
                        'priority' => 'Medium',
                        'epic' => 'Service Reliability Monitoring',
                        'start_date' => $now->copy()->addDays(5),
                        'due_date' => $now->copy()->addWeeks(4),
                        'created_by' => 'admin.test@ptsi.co.id',
                        'assignees' => [
                            'business.analyst@ptsi.co.id',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function generateTicketUuid(Project $project): string
    {
        $prefix = strtoupper($project->ticket_prefix ?? 'TKT');

        return sprintf('%s-%s', $prefix, strtoupper(Str::random(6)));
    }
}
