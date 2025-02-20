<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('title');
            $table->string('category');
            $table->text('description');

            // DateTime
            $table->dateTime('start_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone');

            // Location
            $table->enum('event_type', ['physical', 'virtual', 'hybrid']);
            $table->string('meeting_link')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('hide_address')->default(false);

            // Details
            $table->integer('max_participants')->nullable();
            $table->integer('min_age')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('rules')->nullable();
            $table->text('notes')->nullable();

            // Metadata
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->integer('current_participants')->default(0);

            $table->timestamps();
        });

        // Create pivot table for event participants
        Schema::create('event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_user');
        Schema::dropIfExists('events');
    }
};
