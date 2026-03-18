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
        Schema::create('metric_page_views', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('occurred_at');
            $table->string('route', 120);
            $table->string('event_type', 40)->default('page_view');
            $table->string('category', 80)->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['occurred_at', 'event_type']);
            $table->index(['event_type', 'category']);
            $table->index('route');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_page_views');
    }
};
