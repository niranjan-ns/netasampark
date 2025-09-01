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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->string('message_id')->unique(); // external message ID
            $table->string('type'); // sms, whatsapp, email, voice
            $table->string('direction'); // inbound, outbound
            $table->string('from');
            $table->string('to');
            $table->text('content');
            $table->json('metadata')->nullable(); // delivery status, timestamps, etc.
            $table->string('status')->default('pending'); // pending, sent, delivered, failed, read, replied
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->decimal('cost', 8, 4)->default(0); // message cost
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id', 'type']);
            $table->index(['organization_id', 'direction']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'from']);
            $table->index(['organization_id', 'to']);
            $table->index(['campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
