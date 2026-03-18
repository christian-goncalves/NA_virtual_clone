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
        Schema::create('metric_meeting_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('measured_at');
            $table->unsignedInteger('in_progress_count')->default(0);
            $table->unsignedInteger('within_1h_count')->default(0);
            $table->unsignedInteger('within_6h_count')->default(0);
            $table->timestamps();

            $table->index('measured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_meeting_snapshots');
    }
};
