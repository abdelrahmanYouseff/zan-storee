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
        Schema::table('visitors', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('visitors', 'country_code')) {
                $table->string('country_code')->nullable()->after('country');
            }
            if (!Schema::hasColumn('visitors', 'region')) {
                $table->string('region')->nullable()->after('city');
            }
            if (!Schema::hasColumn('visitors', 'page_visited')) {
                $table->string('page_visited')->nullable()->after('region');
            }

            // Drop old columns if they exist
            if (Schema::hasColumn('visitors', 'page_url')) {
                $table->dropColumn('page_url');
            }
            if (Schema::hasColumn('visitors', 'device_type')) {
                $table->dropColumn('device_type');
            }
            if (Schema::hasColumn('visitors', 'browser')) {
                $table->dropColumn('browser');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            // Restore old structure
            $table->dropColumn(['country_code', 'region', 'page_visited']);
            $table->string('page_url')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
        });
    }
};
