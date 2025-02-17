<?php

namespace App\Providers\ProgressiveSeeders\Console\Commands;

use App\Providers\ProgressiveSeeders\Models\SeederHistory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProgressiveSeederCommand extends Command
{
    protected $signature = 'progressive-seeder:run {class_name?}';

    protected $description = 'Run newest seeders automatically';

    public function handle()
    {
        $className = $this->argument('class_name');
        $batchNumber = $this->getBatchNumber();

        $this->info('Running seeders...');

        if ($className) {
            Artisan::call(
                'db:seed', [
                    '--class' => $className,
                ]
            );
            $this->info('Seeder executed: '.$className);
            $this->addClassToSeedersHistory($className, $batchNumber);
        } else {
            $alreadyRunClassNames = SeederHistory::query()
                ->pluck('class_name')
                ->toArray();

            $alreadyRunClassNames[] = 'DatabaseSeeder';

            $allClassNames = DatabaseSeeder::seeders();

            foreach ($allClassNames as $className) {
                if (! in_array($className, $alreadyRunClassNames)) {
                    Artisan::call(
                        'db:seed', [
                            '--class' => $className,
                        ]
                    );
                    $this->addClassToSeedersHistory($className, $batchNumber);
                    usleep(200000);
                }
            }
        }
    }

    private function addClassToSeedersHistory($className, $batchNumber)
    {
        SeederHistory::query()
            ->create(
                [
                    'class_name' => $className,
                    'batch' => $batchNumber,
                ]
            );

        $this->info('Seeder executed: '.$className);
    }

    private function getBatchNumber(): int
    {
        $lastSeederHistory = SeederHistory::latest()->first();

        return is_null($lastSeederHistory) ? 1 : $lastSeederHistory->batch + 1;
    }
}
