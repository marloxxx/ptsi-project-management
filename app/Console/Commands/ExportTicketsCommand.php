<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exports\TicketsExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportTicketsCommand extends Command
{
    protected $signature = 'tickets:export {path=exports/tickets.xlsx : Relative path (within storage/app) for the exported file}';

    protected $description = 'Export tickets to an Excel file';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $directory = trim(dirname($path), '.');

        if ($directory !== '' && $directory !== '/') {
            Storage::disk('local')->makeDirectory($directory);
        }

        try {
            $stored = Excel::store(new TicketsExport, $path, 'local');

            if (! $stored) {
                $this->components->error('Unable to export tickets. Please check the storage directory permissions.');

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            Log::error('Failed to export tickets.', [
                'exception' => $exception,
                'path' => $path,
            ]);

            $this->components->error('Export failed. Check the logs for more details.');

            return self::FAILURE;
        }

        $this->components->info(sprintf('Tickets exported to storage/app/%s', $path));

        return self::SUCCESS;
    }
}
