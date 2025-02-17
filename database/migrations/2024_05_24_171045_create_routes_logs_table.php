<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'routes_logs', function (Blueprint $table) {
                $table->id();
                $table->string('route');
                $table->string('method', 10);
                $table->decimal('duration', 10, 3); // Précision pour stocker des millisecondes
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes_logs');
    }
};
