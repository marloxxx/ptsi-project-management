<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Actions\Users\SyncSiportalUsers;
use Illuminate\Console\Command;

class SyncSiportalUsersCommand extends Command
{
    protected $signature = 'siportal:sync-users';

    protected $description = 'Sync users and organizational units from SI Portal.';

    public function handle(SyncSiportalUsers $syncSiportalUsers): int
    {
        $this->info('Starting SI Portal user sync...');

        $summary = $syncSiportalUsers->execute();

        $this->table(
            ['Entity', 'Synced', 'Created', 'Updated', 'Skipped', 'Failed'],
            [
                [
                    'Units',
                    $summary['units']['synced'],
                    $summary['units']['created'],
                    $summary['units']['updated'],
                    $summary['units']['skipped'],
                    '-', // not tracked for units
                ],
                [
                    'Users',
                    $summary['users']['synced'],
                    $summary['users']['created'],
                    $summary['users']['updated'],
                    $summary['users']['skipped'],
                    $summary['users']['failed'],
                ],
            ]
        );

        if (! empty($summary['errors'])) {
            $this->warn('Some records failed to sync. Check logs for details.');
        }

        $this->info('SI Portal user sync completed.');

        return self::SUCCESS;
    }
}
