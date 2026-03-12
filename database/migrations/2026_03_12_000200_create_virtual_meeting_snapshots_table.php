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
        Schema::create('virtual_meeting_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('context', 100);
            $table->json('payload');
            $table->string('payload_hash', 40)->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['context', 'captured_at']);
            $table->index(['context', 'payload_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_meeting_snapshots');
    }
};

