<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Imports\TicketsImport;
use App\Models\Project;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TicketsImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fails_when_file_missing(): void
    {
        $this->artisan('tickets:import', ['path' => 'missing.xlsx'])
            ->assertExitCode(1)
            ->expectsOutputToContain('File not found');
    }

    public function test_it_dispatches_excel_import(): void
    {
        $project = Project::factory()->create();
        $status = TicketStatus::factory()->for($project)->create();
        $priority = TicketPriority::factory()->create();
        $user = User::factory()->create();

        $relativePath = 'imports/tickets.xlsx';
        Storage::disk('local')->put($relativePath, 'dummy');
        $absolutePath = Storage::disk('local')->path($relativePath);

        Excel::fake();

        $this->artisan('tickets:import', ['path' => $relativePath, '--update-existing' => true])
            ->assertExitCode(0);

        Excel::assertImported($absolutePath, function ($import) use ($project, $status, $priority, $user): bool {
            $this->assertInstanceOf(TicketsImport::class, $import);
            auth()->login($user);

            $import->collection(collect([
                [
                    'project_id' => $project->id,
                    'ticket_status_id' => $status->id,
                    'priority_id' => $priority->id,
                    'name' => 'Imported ticket',
                    'assignee_ids' => (string) $user->id,
                ],
            ]));

            return true;
        });
    }
}
