<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('metric_page_views', function (Blueprint $table): void {
            $table->string('event_uuid', 26)->nullable()->after('id');
        });

        DB::table('metric_page_views')
            ->whereNull('event_uuid')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('metric_page_views')
                        ->where('id', $row->id)
                        ->update(['event_uuid' => (string) Str::ulid()]);
                }
            });

        Schema::table('metric_page_views', function (Blueprint $table): void {
            $table->unique('event_uuid', 'metric_page_views_event_uuid_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metric_page_views', function (Blueprint $table): void {
            $table->dropUnique('metric_page_views_event_uuid_unique');
            $table->dropColumn('event_uuid');
        });
    }
};
