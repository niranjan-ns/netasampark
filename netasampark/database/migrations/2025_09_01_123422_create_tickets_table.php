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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // support, complaint, suggestion, etc.
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('open'); // open, in-progress, resolved, closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('metadata')->nullable(); // source, tags, etc.
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'priority']);
            $table->index(['organization_id', 'category']);
            $table->index(['assigned_to']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
