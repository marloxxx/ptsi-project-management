<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Imports\TicketsImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportTicketsCommand extends Command
{
    protected $signature = 'tickets:import {path : Relative path (within storage/app) of the Excel file} {--update-existing : Update tickets when the ID is provided}';

    protected $description = 'Import tickets from an Excel file';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $allowUpdates = (bool) $this->option('update-existing');

        if (! Storage::disk('local')->exists($path)) {
            $this->components->error(sprintf('File not found at storage/app/%s', $path));

            return self::FAILURE;
        }

        $absolutePath = Storage::disk('local')->path($path);

        Excel::import(new TicketsImport($allowUpdates), $absolutePath, null, \Maatwebsite\Excel\Excel::XLSX);

        $this->components->info('Tickets imported successfully.');

        return self::SUCCESS;
    }
}
