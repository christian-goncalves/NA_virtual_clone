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
        Schema::create('virtual_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('meeting_platform')->nullable();
            $table->text('meeting_url')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('phone')->nullable();
            $table->string('region')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('neighborhood')->nullable();
            $table->json('format_labels')->nullable();
            $table->string('type_label')->nullable();
            $table->json('interest_labels')->nullable();
            $table->string('weekday')->nullable();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->boolean('is_open')->default(false);
            $table->boolean('is_study')->default(false);
            $table->boolean('is_lgbt')->default(false);
            $table->boolean('is_women')->default(false);
            $table->boolean('is_hybrid')->default(false);
            $table->text('source_url')->nullable();
            $table->string('source_hash')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->boolean('auto_join_enabled')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'weekday', 'start_time']);
            $table->index('external_id');
            $table->index('source_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_meetings');
    }
};
