<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // rally, meeting, press-conference, etc.
            $table->string('status')->default('scheduled'); // scheduled, ongoing, completed, cancelled
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('location')->nullable();
            $table->json('coordinates')->nullable(); // latitude, longitude
            $table->json('attendees')->nullable(); // expected attendees
            $table->json('settings')->nullable(); // event settings
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'type']);
            $table->index(['organization_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
