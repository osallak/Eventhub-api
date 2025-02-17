<?php

namespace App\Providers\ProgressiveSeeders\Console\Commands;

use App\Providers\ProgressiveSeeders\Models\SeederHistory;
use Illuminate\Console\Command;

class ProgressiveSeederRollbackCommand extends Command
{
    protected $signature = 'progressive-seeder:rollback';

    protected $description = 'Roll back latest executed seeders';

    public function handle()
    {
        $this->info('Rolling back seeders...');
        $lastRunSeedersBatch = SeederHistory::whereBatch($this->getLastBatchNumber())->latest()->get();

        foreach ($lastRunSeedersBatch as $seederHistory) {
            $className = $seederHistory->class_name;
            $seeder = app($className);
            if (method_exists($seeder, 'rollback')) {
                $seeder->rollback();
                $seederHistory->delete();
                $this->info('Seeder rolled back: '.$className);
            } else {
                throw new \Exception("Seeder's rollback method is not defined");
            }
        }
    }

    private function getAllClassNamesFromSeedersFolder(): array
    {
        $allFileNames = \File::files('database/seeders');
        $allClassNames = [];

        foreach ($allFileNames as $fileName) {
            $allClassNames[] = pathinfo($fileName)['filename'];
        }

        return $allClassNames;
    }

    private function getLastBatchNumber(): int
    {
        $lastSeederHistory = SeederHistory::latest()->first();

        return is_null($lastSeederHistory) ? 1 : $lastSeederHistory->batch;
    }
}
