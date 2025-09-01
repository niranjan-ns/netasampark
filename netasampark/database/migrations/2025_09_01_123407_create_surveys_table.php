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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // voter-preference, issue-tracking, feedback, etc.
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->json('questions'); // survey questions and options
            $table->json('target_audience')->nullable(); // filters, segments
            $table->integer('total_responses')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('settings')->nullable(); // survey settings
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
