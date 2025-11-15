<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Exports\TicketsExport;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TicketsExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_excel_export(): void
    {
        Ticket::factory()->count(3)->create();

        Excel::fake();

        $this->artisan('tickets:export', ['path' => 'exports/testing.xlsx'])
            ->assertExitCode(0);

        Excel::assertStored('exports/testing.xlsx', 'local', function ($export): bool {
            return $export instanceof TicketsExport
                && $export->collection()->count() === 3;
        });
    }
}
