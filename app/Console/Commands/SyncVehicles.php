<?php

namespace App\Console\Commands;

use App\Services\VehicleSyncService;
use Illuminate\Console\Command;

class SyncVehicles extends Command
{
    protected $signature = 'sync:vehicles
        {--limit= : Fetch at most N vehicles (for smoke-testing). Omit for a full sync.}';

    protected $description = 'Fetch all vehicles from Classic Arlington API and update local inventory';

    public function handle(VehicleSyncService $service): int
    {
        $limit = null;

        if ($this->option('limit') !== null) {
            $rawLimit = $this->option('limit');

            if (!ctype_digit((string) $rawLimit) || (int) $rawLimit < 1) {
                $this->error('--limit must be a positive integer.');
                return self::FAILURE;
            }

            $limit = (int) $rawLimit;
        }

        if ($limit !== null) {
            $this->warn("TEST MODE: fetching at most {$limit} vehicle(s). Prune step skipped.");
            $this->newLine();
        }

        $this->info('Starting vehicle sync…');
        $startedAt = now();

        try {
            $result = $service->sync($limit);
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $elapsed = round(now()->diffInSeconds($startedAt));

        $this->newLine();
        $this->line('  ┌───────────────────────────┐');
        $this->line('  │       Sync Summary         │');
        $this->line('  ├───────────────────────────┤');
        $this->line("  │  Fetched  : {$this->pad($result['fetched'])}  │");
        $this->line("  │  Inserted : {$this->pad($result['inserted'])}  │");
        $this->line("  │  Updated  : {$this->pad($result['updated'])}  │");
        $this->line("  │  Deleted  : {$this->pad($limit !== null ? 'skipped (--limit)' : $result['deleted'])}  │");
        $this->line("  │  Time     : {$this->pad($elapsed . 's')}  │");
        $this->line('  └───────────────────────────┘');
        $this->newLine();

        if (!empty($result['errors'])) {
            $this->warn('Errors during sync:');
            foreach ($result['errors'] as $err) {
                $this->warn("  • {$err}");
            }
            $this->newLine();
        }

        if ($result['fetched'] === 0 && !empty($result['errors'])) {
            $this->error('Sync completed with errors and no data was written.');
            return self::FAILURE;
        }

        $this->info($limit !== null
            ? "Test sync complete. {$result['inserted']} inserted, {$result['updated']} updated."
            : 'Full sync complete. Database is up to date.'
        );

        return self::SUCCESS;
    }

    private function pad(mixed $value, int $width = 20): string
    {
        return str_pad((string) $value, $width);
    }
}
