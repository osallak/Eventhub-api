<?php

namespace App\Providers;

use Doctrine\DBAL\Schema\SchemaException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Blueprint::macro(
            'upload', function ($columnName) {
                if (Schema::hasTable('uploads')) {
                    $this->foreignId($columnName)->nullable()->constrained('uploads')->nullOnDelete();
                } else {
                    throw SchemaException::tableDoesNotExist('uploads');
                }
            }
        );

        Blueprint::macro(
            'dropUpload', function ($columnName) {
                if (Schema::hasColumn($this->getTable(), $columnName)) {
                    $this->dropForeign([$columnName]);
                    $this->dropColumn($columnName);
                } else {
                    throw SchemaException::columnDoesNotExist($columnName, $this->getTable());
                }
            }
        );
    }
}
