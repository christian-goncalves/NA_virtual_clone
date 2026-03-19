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
        Schema::create('metric_hourly_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('hour_bucket');
            $table->string('metric_key', 60);
            $table->string('dimension', 160)->nullable();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('avg_duration_ms')->nullable();
            $table->unsignedInteger('p95_duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['hour_bucket', 'metric_key', 'dimension'], 'metric_hourly_unique');
            $table->index(['metric_key', 'hour_bucket']);
            $table->index(['hour_bucket', 'total_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_hourly_aggregates');
    }
};
