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
        Schema::create('metric_request_metrics', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('occurred_at');
            $table->string('route', 160);
            $table->string('http_method', 10);
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->string('session_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['occurred_at', 'route']);
            $table->index(['route', 'duration_ms']);
            $table->index(['status_code', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_request_metrics');
    }
};
