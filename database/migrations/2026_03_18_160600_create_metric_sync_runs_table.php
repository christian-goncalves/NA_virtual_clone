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
        Schema::create('metric_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('meetings_found')->nullable();
            $table->unsignedInteger('meetings_saved')->nullable();
            $table->unsignedInteger('meetings_updated')->nullable();
            $table->unsignedInteger('meetings_inactivated')->nullable();
            $table->text('error_message')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamps();

            $table->index(['started_at', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_sync_runs');
    }
};
