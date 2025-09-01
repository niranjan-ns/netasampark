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
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('voter_id')->unique(); // EC voter ID
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('address')->nullable();
            $table->string('constituency');
            $table->string('district');
            $table->string('state');
            $table->string('booth_number')->nullable();
            $table->string('part_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->json('demographics')->nullable(); // age group, education, occupation, etc.
            $table->json('tags')->nullable(); // influencer, supporter, undecided, etc.
            $table->json('consent')->nullable(); // sms, whatsapp, email, voice
            $table->string('status')->default('active'); // active, inactive, deceased
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id', 'constituency']);
            $table->index(['organization_id', 'booth_number']);
            $table->index(['organization_id', 'phone']);
            $table->index(['organization_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
